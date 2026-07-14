<?php

namespace Reactions\Service;

use Reactions\Dto\VisitorIdentity;
use Reactions\Enum\Period;
use Reactions\Model\Reaction;
use Reactions\Model\ReactionAggregate;
use Reactions\Model\ReactionType;
use Reactions\Reactions;
use Reactions\Support\Counts;

class AggregateService
{
    private const LIKE_TYPES = ['like', 'up'];
    private const DISLIKE_TYPES = ['dislike', 'down'];

    public function __construct(
        private readonly Reactions $reactions,
        private ?CacheService $cache = null,
        private ?TrendingCalculator $trending = null,
    ) {
    }

    public function recount(string $classKey, int $objectId, string $context): ReactionAggregate
    {
        $counts = $this->countByType($classKey, $objectId, $context);
        $metrics = $this->metrics($counts);
        $latestAt = $this->latestReactionAt($classKey, $objectId, $context) ?: time();
        $aggregate = $this->loadOrCreate($classKey, $objectId, $context);
        $aggregate->fromArray([
            'counts' => $counts,
            'total' => $metrics['total'],
            'likes' => $metrics['likes'],
            'dislikes' => $metrics['dislikes'],
            'rating' => $metrics['rating'],
            'trending_score' => $this->trending()->score($metrics['rating'], $latestAt),
            'updated_at' => time(),
        ], '', false, true);
        $aggregate->save();
        $this->invalidateCache($classKey, $objectId, $context);

        return $aggregate;
    }

    public function getCounts(string $classKey, int $objectId, string $context = 'web'): array
    {
        $cacheKey = 'counts/' . $classKey . '/' . $objectId . '/' . $context;
        $cached = $this->cache()->get($cacheKey);
        if (is_array($cached)) {
            $normalized = Counts::normalize($cached);
            // Only trust cache when it is already a clean int map.
            if ($normalized === $cached) {
                return $normalized;
            }
            // Junk strings / wrong shapes → drop and reload from DB.
            $this->cache()->delete($cacheKey);
        } elseif (is_string($cached) && $cached !== '') {
            $normalized = Counts::normalize($cached);
            if ($normalized !== []) {
                $this->cache()->set($cacheKey, $normalized, 300);

                return $normalized;
            }
            $this->cache()->delete($cacheKey);
        }

        $aggregate = $this->reactions->modx->getObject(ReactionAggregate::class, [
            'object_class' => $classKey,
            'object_id' => $objectId,
            'context' => $context,
        ]);
        $counts = Counts::normalize($aggregate ? $aggregate->get('counts') : []);
        $this->cache()->set($cacheKey, $counts, 300);

        return $counts;
    }

    /** @return list<string> */
    public function getUserReactions(
        string $classKey,
        int $objectId,
        string $context,
        VisitorIdentity $identity,
    ): array {
        // Per-visitor state is not cached: it changes on every react and keyed by fingerprint.
        $names = [];
        foreach ($this->reactionsFor($classKey, $objectId, $context, $identity->fingerprint) as $reaction) {
            $type = $this->loadType((int) $reaction->get('type_id'));
            if ($type) {
                $names[] = (string) $type->get('name');
            }
        }

        return $names;
    }

    /** @return list<ReactionAggregate> */
    public function listTop(
        string $classKey,
        string $sortField,
        Period $period,
        int $limit,
        string $context = '',
    ): array {
        $allowed = ['likes', 'dislikes', 'rating', 'total', 'trending_score'];
        $sortField = in_array($sortField, $allowed, true) ? $sortField : 'likes';

        if ($period === Period::All) {
            $criteria = ['object_class' => $classKey];
            if ($context !== '') {
                $criteria['context'] = $context;
            }

            return array_values($this->reactions->modx->getCollection(
                ReactionAggregate::class,
                $criteria,
                false,
                true,
                [$sortField => 'DESC'],
                $limit
            ));
        }

        return $this->listTopForPeriod($classKey, $sortField, $period, $limit, $context);
    }

    public function invalidateCache(string $classKey, int $objectId, string $context): void
    {
        $this->cache()->delete('counts/' . $classKey . '/' . $objectId . '/' . $context);
    }

    private function loadOrCreate(string $classKey, int $objectId, string $context): ReactionAggregate
    {
        $aggregate = $this->reactions->modx->getObject(ReactionAggregate::class, [
            'object_class' => $classKey,
            'object_id' => $objectId,
            'context' => $context,
        ]);

        if ($aggregate) {
            return $aggregate;
        }

        $aggregate = $this->reactions->modx->newObject(ReactionAggregate::class);
        $aggregate->fromArray([
            'object_class' => $classKey,
            'object_id' => $objectId,
            'context' => $context,
            'counts' => [],
            'total' => 0,
            'likes' => 0,
            'dislikes' => 0,
            'rating' => 0,
            'trending_score' => 0.0,
            'updated_at' => time(),
        ], '', true, true);

        return $aggregate;
    }

    /** @return array<string, int> */
    private function countByType(string $classKey, int $objectId, string $context): array
    {
        $counts = [];
        foreach ($this->reactionsFor($classKey, $objectId, $context) as $reaction) {
            $type = $this->loadType((int) $reaction->get('type_id'));
            if (!$type) {
                continue;
            }
            $name = (string) $type->get('name');
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        return $counts;
    }

    /** @return list<Reaction> */
    private function reactionsFor(
        string $classKey,
        int $objectId,
        string $context,
        ?string $fingerprint = null,
    ): array {
        $criteria = [
            'object_class' => $classKey,
            'object_id' => $objectId,
            'context' => $context,
        ];
        if ($fingerprint !== null) {
            $criteria['fingerprint'] = $fingerprint;
        }

        return array_values($this->reactions->modx->getCollection(Reaction::class, $criteria));
    }

    private function latestReactionAt(string $classKey, int $objectId, string $context): int
    {
        $rows = $this->reactions->modx->getCollection(
            Reaction::class,
            ['object_class' => $classKey, 'object_id' => $objectId, 'context' => $context],
            false,
            true,
            ['created_at' => 'DESC'],
            1
        );
        $reaction = reset($rows);

        return $reaction ? (int) $reaction->get('created_at') : 0;
    }

    /** @param array<string, int> $counts @return array{likes:int,dislikes:int,rating:int,total:int} */
    private function metrics(array $counts): array
    {
        $likes = $this->sumTypes($counts, self::LIKE_TYPES);
        $dislikes = $this->sumTypes($counts, self::DISLIKE_TYPES);

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'rating' => $likes - $dislikes,
            'total' => Counts::total($counts),
        ];
    }

    /** @param array<string, int> $counts @param list<string> $types */
    private function sumTypes(array $counts, array $types): int
    {
        $sum = 0;
        foreach ($types as $type) {
            $sum += (int) ($counts[$type] ?? 0);
        }

        return $sum;
    }

    /** @return list<ReactionAggregate> */
    private function listTopForPeriod(
        string $classKey,
        string $sortField,
        Period $period,
        int $limit,
        string $context,
    ): array {
        $criteria = [
            'object_class' => $classKey,
            'created_at:>=' => time() - (int) $period->seconds(),
        ];
        if ($context !== '') {
            $criteria['context'] = $context;
        }

        $buckets = [];
        foreach ($this->reactions->modx->getCollection(Reaction::class, $criteria) as $reaction) {
            $type = $this->loadType((int) $reaction->get('type_id'));
            if (!$type) {
                continue;
            }

            $key = $reaction->get('object_id') . ':' . $reaction->get('context');
            if (!isset($buckets[$key])) {
                $buckets[$key] = ['object_id' => (int) $reaction->get('object_id'), 'context' => (string) $reaction->get('context'), 'counts' => [], 'latest_at' => 0];
            }

            $name = (string) $type->get('name');
            $buckets[$key]['counts'][$name] = ($buckets[$key]['counts'][$name] ?? 0) + 1;
            $buckets[$key]['latest_at'] = max($buckets[$key]['latest_at'], (int) $reaction->get('created_at'));
        }

        $aggregates = [];
        foreach ($buckets as $bucket) {
            $metrics = $this->metrics($bucket['counts']);
            $aggregate = $this->reactions->modx->newObject(ReactionAggregate::class);
            $aggregate->fromArray([
                'object_class' => $classKey,
                'object_id' => $bucket['object_id'],
                'context' => $bucket['context'],
                'counts' => $bucket['counts'],
                'total' => $metrics['total'],
                'likes' => $metrics['likes'],
                'dislikes' => $metrics['dislikes'],
                'rating' => $metrics['rating'],
                'trending_score' => $this->trending()->score($metrics['rating'], $bucket['latest_at']),
                'updated_at' => time(),
            ], '', true, true);
            $aggregates[] = $aggregate;
        }

        usort($aggregates, static fn (ReactionAggregate $a, ReactionAggregate $b): int =>
            ((float) $b->get($sortField)) <=> ((float) $a->get($sortField)));

        return array_slice($aggregates, 0, max(0, $limit));
    }

    /** @return array<string, int> */
    public function decodeCounts(mixed $counts): array
    {
        return Counts::normalize($counts);
    }

    private function loadType(int $typeId): ?ReactionType
    {
        if ($typeId <= 0) {
            return null;
        }

        $type = $this->reactions->modx->getObject(ReactionType::class, $typeId);

        return $type instanceof ReactionType ? $type : null;
    }

    private function cache(): CacheService
    {
        return $this->cache ??= new CacheService($this->reactions);
    }

    private function trending(): TrendingCalculator
    {
        return $this->trending ??= $this->reactions->getTrendingCalculator();
    }
}
