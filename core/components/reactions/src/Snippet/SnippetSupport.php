<?php

namespace Reactions\Snippet;

use MODX\Revolution\modX;
use Reactions\Enum\Period;
use Reactions\Model\ReactionSet;
use Reactions\Model\ReactionSetType;
use Reactions\Model\ReactionType;
use Reactions\Reactions;
use Reactions\Support\TypeFilter;

trait SnippetSupport
{
    /** @param array<string, mixed> $props */
    protected function resolveObjectId(modX $modx, array $props): int
    {
        $objectId = (int) ($props['object'] ?? 0);
        if ($objectId <= 0 && $modx->resource) {
            $objectId = (int) $modx->resource->get('id');
        }

        return $objectId;
    }

    /** @param array<string, mixed> $props */
    protected function resolveContext(modX $modx, array $props): string
    {
        $context = (string) ($props['context'] ?? '');

        return $context !== '' ? $context : (string) $modx->context->get('key');
    }

    /** @param array<string, mixed> $props */
    protected function parsePeriod(array $props, Period $default = Period::All): Period
    {
        $value = (string) ($props['period'] ?? '');

        return Period::tryFrom($value) ?? $default;
    }

    /** @return list<ReactionType> */
    protected function loadSetTypes(Reactions $reactions, string $setKey): array
    {
        $set = $reactions->modx->getObject(ReactionSet::class, ['key' => $setKey, 'active' => true]);
        if (!$set) {
            return [];
        }

        $links = $reactions->modx->getCollection(
            ReactionSetType::class,
            ['set_id' => (int) $set->get('id')],
            false,
            true,
            ['ordering' => 'ASC'],
        );

        $types = [];
        foreach ($links as $link) {
            $type = $link->getOne('Type');
            if ($type instanceof ReactionType && (bool) $type->get('active')) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @param array<string, mixed> $scriptProperties
     *
     * @return list<ReactionType>
     */
    protected function loadFilteredSetTypes(Reactions $reactions, string $setKey, array $scriptProperties): array
    {
        $types = $this->loadSetTypes($reactions, $setKey);
        $allow = TypeFilter::resolveAllowList(
            $setKey,
            (string) ($scriptProperties['types'] ?? ''),
            (string) $reactions->getOption('fullTypes', ''),
        );

        return TypeFilter::filterTypes($types, $allow);
    }

    /**
     * @param array<string, int> $counts
     *
     * @return array{likes: int, dislikes: int, rating: int, total: int}
     */
    protected function metricsFromCounts(array $counts): array
    {
        $likes = $this->sumTypeCounts($counts, ['like', 'up']);
        $dislikes = $this->sumTypeCounts($counts, ['dislike', 'down']);

        return [
            'likes' => $likes,
            'dislikes' => $dislikes,
            'rating' => $likes - $dislikes,
            'total' => array_sum($counts),
        ];
    }

    /**
     * @param array<string, int> $counts
     * @param list<string>       $types
     */
    private function sumTypeCounts(array $counts, array $types): int
    {
        $sum = 0;
        foreach ($types as $type) {
            $sum += (int) ($counts[$type] ?? 0);
        }

        return $sum;
    }
}
