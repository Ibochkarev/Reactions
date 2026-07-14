<?php

namespace Reactions\Service;

use Reactions\Dto\ReactionRequest;
use Reactions\Dto\ReactionResult;
use Reactions\Dto\VisitorIdentity;
use Reactions\Enum\ReactionAction;
use Reactions\Exception\ObjectNotFound;
use Reactions\Exception\ReactionNotAllowed;
use Reactions\Model\Reaction;
use Reactions\Model\ReactionAggregate;
use Reactions\Model\ReactionSet;
use Reactions\Model\ReactionSetType;
use Reactions\Model\ReactionType;
use Reactions\Reactions;
use Reactions\Support\ObjectLookup;
use Reactions\Support\TypeFilter;

class ReactionService
{
    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function react(ReactionRequest $request): ReactionResult
    {
        return $this->execute(
            $request,
            fn (...$args) => $this->applyReaction(...$args),
            true
        );
    }

    public function unreact(ReactionRequest $request): ReactionResult
    {
        return $this->execute($request, function (ReactionRequest $req, VisitorIdentity $identity, ReactionType $type): ReactionAction {
            $existing = $this->findByType($req, $identity, (int) $type->get('id'));
            if ($existing) {
                $existing->remove();
            }
            return ReactionAction::Removed;
        }, false);
    }

    private function execute(ReactionRequest $request, callable $mutator, bool $notify): ReactionResult
    {
        $identity = $this->reactions->getIdentityResolver()->resolve($this->reactions);
        $this->guardAccess($identity);
        $this->assertObjectExists($request);
        $type = $this->loadType($request->typeName);
        $set = $this->loadSet($request);
        $this->assertTypeInSet($type, $set);
        $this->fireBefore($request, $identity, $type, $set);
        $action = $this->runInTransaction(fn () => $mutator($request, $identity, $type, $set));
        $this->fireAfter($request, $identity, $type, $action);
        $aggregate = $this->reactions->getAggregateService()->recount($request->classKey, $request->objectId, $request->context);
        $result = $this->buildResult($request, $identity, $aggregate, $action);
        $this->reactions->getWebhookDispatcher()->dispatch($result, $request);
        if ($notify) {
            $this->reactions->getNotificationService()->notify($request, $result);
        }
        return $result;
    }

    private function guardAccess(VisitorIdentity $identity): void
    {
        $detector = $this->reactions->getBotDetector();
        $modx = $this->reactions->modx;
        $blockBots = (bool) $modx->getOption('reactions_block_bots', null, true);

        if ($blockBots && $detector->isBot()) {
            throw new ReactionNotAllowed($modx->lexicon('reactions_err_bot'));
        }
        if ($detector->isBlocked($identity)) {
            throw new ReactionNotAllowed($modx->lexicon('reactions_err_forbidden'));
        }

        $this->reactions->getRateLimiter()->allow($identity);
    }

    private function assertObjectExists(ReactionRequest $request): void
    {
        if (!$this->objectExists($request->classKey, $request->objectId)) {
            throw new ObjectNotFound($this->reactions->modx->lexicon('reactions_err_nf'));
        }
    }

    /**
     * Resolve aliases/STI (e.g. msProduct → MiniShop3\Model\msProduct / modResource).
     */
    private function objectExists(string $classKey, int $objectId): bool
    {
        return ObjectLookup::exists($this->reactions->modx, $classKey, $objectId);
    }

    private function loadType(string $typeName): ReactionType
    {
        $type = $this->reactions->modx->getObject(ReactionType::class, ['name' => $typeName, 'active' => true]);
        if (!$type) {
            throw new ObjectNotFound($this->reactions->modx->lexicon('reactions_err_type'));
        }

        return $type;
    }

    private function loadSet(ReactionRequest $request): ReactionSet
    {
        $setKey = $request->setKey !== '' ? $request->setKey : (string) $this->reactions->getOption('defaultSet', 'updown');
        $set = $this->reactions->modx->getObject(ReactionSet::class, ['key' => $setKey, 'active' => true]);
        if (!$set) {
            throw new ObjectNotFound($this->reactions->modx->lexicon('reactions_err_set'));
        }

        return $set;
    }

    private function assertTypeInSet(ReactionType $type, ReactionSet $set): void
    {
        $link = $this->reactions->modx->getObject(ReactionSetType::class, [
            'set_id' => (int) $set->get('id'),
            'type_id' => (int) $type->get('id'),
        ]);
        if (!$link) {
            throw new ReactionNotAllowed($this->reactions->modx->lexicon('reactions_err_forbidden'));
        }

        $allow = TypeFilter::resolveAllowList(
            (string) $set->get('key'),
            '',
            (string) $this->reactions->getOption('fullTypes', ''),
        );
        if ($allow === null) {
            return;
        }

        $name = strtolower((string) $type->get('name'));
        if (!in_array($name, $allow, true)) {
            throw new ReactionNotAllowed($this->reactions->modx->lexicon('reactions_err_forbidden'));
        }
    }

    private function applyReaction(
        ReactionRequest $request,
        VisitorIdentity $identity,
        ReactionType $type,
        ReactionSet $set,
    ): ReactionAction {
        $typeId = (int) $type->get('id');
        $single = $this->isSingleReactionMode($request, $set);
        $sameType = $this->findByType($request, $identity, $typeId);
        $others = $this->findOthers($request, $identity, $typeId);

        if ($sameType) {
            $sameType->remove();
            if ($single) {
                $this->removeMany($others);
            }

            return ReactionAction::Removed;
        }

        if ($single && $others !== []) {
            $changed = $this->changeType($others[0], $typeId, $identity);
            $this->removeMany(array_slice($others, 1));

            return $changed ? ReactionAction::Changed : ReactionAction::Added;
        }

        if ($single) {
            $this->removeMany($others);
        }

        $this->createRecord($request, $identity, $typeId);

        return ReactionAction::Added;
    }

    private function isSingleReactionMode(ReactionRequest $request, ReactionSet $set): bool
    {
        $allowMultiple = $request->allowMultiple || (bool) $this->reactions->getOption('allowMultiple', false);

        return (bool) $set->get('exclusive') || !$allowMultiple;
    }

    /** @return list<Reaction> */
    private function findOthers(ReactionRequest $request, VisitorIdentity $identity, int $typeId): array
    {
        return array_values($this->reactions->modx->getCollection(Reaction::class, [
            'object_class' => $request->classKey,
            'object_id' => $request->objectId,
            'context' => $request->context,
            'fingerprint' => $identity->fingerprint,
            'type_id:!=' => $typeId,
        ]));
    }

    private function findByType(ReactionRequest $request, VisitorIdentity $identity, int $typeId): ?Reaction
    {
        return $this->reactions->modx->getObject(Reaction::class, [
            'object_class' => $request->classKey,
            'object_id' => $request->objectId,
            'context' => $request->context,
            'fingerprint' => $identity->fingerprint,
            'type_id' => $typeId,
        ]);
    }

    private function createRecord(ReactionRequest $request, VisitorIdentity $identity, int $typeId): void
    {
        $now = time();
        $reaction = $this->reactions->modx->newObject(Reaction::class);
        $reaction->fromArray([
            'object_class' => $request->classKey,
            'object_id' => $request->objectId,
            'context' => $request->context,
            'type_id' => $typeId,
            'user_id' => $identity->userId,
            'fingerprint' => $identity->fingerprint,
            'ip_hash' => $identity->ipHash,
            'session_id' => $identity->sessionId,
            'created_at' => $now,
            'updated_at' => $now,
        ], '', true, true);
        $reaction->save();
    }

    private function changeType(Reaction $reaction, int $typeId, VisitorIdentity $identity): bool
    {
        if ((int) $reaction->get('type_id') === $typeId) {
            return false;
        }

        $reaction->fromArray([
            'type_id' => $typeId,
            'user_id' => $identity->userId,
            'ip_hash' => $identity->ipHash,
            'session_id' => $identity->sessionId,
            'updated_at' => time(),
        ], '', false, true);
        $reaction->save();

        return true;
    }

    /** @param list<Reaction> $reactions */
    private function removeMany(array $reactions): void
    {
        foreach ($reactions as $reaction) {
            $reaction->remove();
        }
    }

    private function fireBefore(
        ReactionRequest $request,
        VisitorIdentity $identity,
        ReactionType $type,
        ReactionSet $set,
    ): void {
        $modx = $this->reactions->modx;
        $modx->invokeEvent('OnBeforeReaction', compact('request', 'identity', 'type', 'set'));
        if (!empty($modx->event->returnedValues['cancel'])) {
            throw new ReactionNotAllowed($modx->lexicon('reactions_err_forbidden'));
        }
    }

    private function fireAfter(ReactionRequest $request, VisitorIdentity $identity, ReactionType $type, ReactionAction $action): void
    {
        $modx = $this->reactions->modx;
        $props = compact('request', 'identity', 'type') + ['action' => $action->value];
        $modx->invokeEvent('OnAfterReaction', $props);
        if ($action === ReactionAction::Removed) {
            $modx->invokeEvent('OnReactionRemoved', $props);
        } elseif ($action === ReactionAction::Changed) {
            $modx->invokeEvent('OnReactionChanged', $props);
        }
    }

    private function buildResult(ReactionRequest $request, VisitorIdentity $identity, ReactionAggregate $aggregate, ReactionAction $action): ReactionResult
    {
        return new ReactionResult(
            $action,
            $this->reactions->getAggregateService()->decodeCounts($aggregate->get('counts')),
            (int) $aggregate->get('total'),
            $this->reactions->getAggregateService()->getUserReactions($request->classKey, $request->objectId, $request->context, $identity),
            $request->typeName,
        );
    }

    private function runInTransaction(callable $callback): mixed
    {
        $modx = $this->reactions->modx;
        $started = method_exists($modx, 'beginTransaction');

        try {
            if ($started) {
                $modx->beginTransaction();
            }
            $result = $callback();
            if ($started) {
                $modx->commit();
            }

            return $result;
        } catch (\Throwable $exception) {
            if ($started && method_exists($modx, 'rollback')) {
                $modx->rollback();
            }

            throw $exception;
        }
    }
}
