<?php

namespace Reactions\Api\Controller\Admin;

use Reactions\Api\Controller\AbstractController;
use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\Reaction;
use Reactions\Model\ReactionAggregate;
use Reactions\Model\ReactionBan;
use Reactions\Model\ReactionType;

class StatsController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $modx = $this->modx();
        $todayStart = strtotime('today');

        JsonResponse::success([
            'data' => [
                'totals' => [
                    'reactions' => (int) $modx->getCount(Reaction::class),
                    'aggregates' => (int) $modx->getCount(ReactionAggregate::class),
                    'types' => (int) $modx->getCount(ReactionType::class),
                    'bans' => (int) $modx->getCount(ReactionBan::class),
                    'today' => (int) $modx->getCount(Reaction::class, ['created_at:>' => $todayStart]),
                ],
                'top_liked' => $this->topByField('likes'),
                'top_trending' => $this->topByField('trending_score'),
            ],
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topByField(string $field): array
    {
        $query = $this->modx()->newQuery(ReactionAggregate::class);
        $query->sortby($field, 'DESC');
        $query->limit(10);

        $items = [];
        foreach ($this->modx()->getCollection(ReactionAggregate::class, $query) as $row) {
            $items[] = [
                'class_key' => $row->get('class_key'),
                'object_id' => (int) $row->get('object_id'),
                'context' => $row->get('context'),
                'likes' => (int) $row->get('likes'),
                'total' => (int) $row->get('total'),
                'trending_score' => (float) $row->get('trending_score'),
            ];
        }

        return $items;
    }
}
