<?php

namespace Reactions\Service;

use MODX\Revolution\modResource;
use MODX\Revolution\modUserMessage;
use MODX\Revolution\modX;
use Reactions\Dto\ReactionRequest;
use Reactions\Dto\ReactionResult;
use Reactions\Enum\ReactionAction;
use Reactions\Reactions;

class NotificationService
{
    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function notify(ReactionRequest $request, ReactionResult $result): void
    {
        if (!$this->reactions->getOption('notifyAuthors', false)) {
            return;
        }

        if ($result->action !== ReactionAction::Added) {
            return;
        }

        if ($request->classKey !== modResource::class && $request->classKey !== 'modResource') {
            return;
        }

        $modx = $this->reactions->modx;
        $resource = $modx->getObject(modResource::class, $request->objectId)
            ?: $modx->getObject('modResource', $request->objectId);
        if (!$resource) {
            return;
        }

        $authorId = (int) $resource->get('createdby');
        if ($authorId <= 0) {
            return;
        }

        $this->sendMessage($modx, $authorId, $request, $resource);
    }

    private function sendMessage(
        modX $modx,
        int $authorId,
        ReactionRequest $request,
        modResource $resource,
    ): void {
        $subject = $modx->lexicon('reactions_success_added');
        $body = sprintf(
            'New reaction "%s" on resource #%d (%s)',
            $request->typeName,
            $request->objectId,
            $resource->get('pagetitle')
        );

        $message = $modx->newObject(modUserMessage::class);
        if (!$message || !$message->fromArray([
            'subject' => $subject,
            'message' => $body,
            'sender' => 0,
            'recipient' => $authorId,
            'private' => true,
            'date_sent' => time(),
        ], '', true, true) || !$message->save()) {
            $modx->log(modX::LOG_LEVEL_WARN, '[Reactions] Failed to notify author #' . $authorId);
        }
    }
}
