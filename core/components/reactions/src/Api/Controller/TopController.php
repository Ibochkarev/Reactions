<?php

namespace Reactions\Api\Controller;

use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\ReactionAggregate;

class TopController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $classKey = $this->queryString('class_key', 'modResource');
        $context = $this->queryString('context');
        $period = $this->parsePeriod($this->queryString('period', 'all'));
        $sort = $this->queryString('sort', 'likes');
        [$limit, $offset] = $this->pagination();

        $sortField = match ($sort) {
            'rating' => 'rating',
            'total' => 'total',
            default => 'likes',
        };

        $aggregate = $this->reactions->getAggregateService();
        $items = $aggregate->listTop($classKey, $sortField, $period, $limit + $offset, $context);
        $items = array_slice($items, $offset, $limit);

        JsonResponse::success([
            'data' => [
                'items' => array_map([$this, 'formatAggregate'], $items),
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($items),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAggregate(ReactionAggregate $aggregate): array
    {
        return [
            'class_key' => $aggregate->get('class_key'),
            'object_id' => (int) $aggregate->get('object_id'),
            'context' => $aggregate->get('context'),
            'counts' => $aggregate->get('counts') ?? [],
            'total' => (int) $aggregate->get('total'),
            'likes' => (int) $aggregate->get('likes'),
            'dislikes' => (int) $aggregate->get('dislikes'),
            'rating' => (int) $aggregate->get('rating'),
            'trending_score' => (float) $aggregate->get('trending_score'),
        ];
    }
}
