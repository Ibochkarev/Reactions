<?php

namespace Reactions\Api\Controller;

use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\ReactionAggregate;

class TrendingController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $classKey = $this->queryString('class_key', 'modResource');
        $context = $this->queryString('context');
        [$limit, $offset] = $this->pagination();

        $aggregate = $this->reactions->getAggregateService();
        $items = $aggregate->listTop($classKey, 'trending_score', $this->parsePeriod('all'), $limit + $offset, $context);
        $items = array_slice($items, $offset, $limit);

        JsonResponse::success([
            'data' => [
                'items' => array_map(static function (ReactionAggregate $row) use ($aggregate): array {
                    return [
                        'class_key' => $row->get('object_class'),
                        'object_id' => (int) $row->get('object_id'),
                        'context' => $row->get('context'),
                        'counts' => $aggregate->decodeCounts($row->get('counts')),
                        'total' => (int) $row->get('total'),
                        'likes' => (int) $row->get('likes'),
                        'dislikes' => (int) $row->get('dislikes'),
                        'rating' => (int) $row->get('rating'),
                        'trending_score' => (float) $row->get('trending_score'),
                    ];
                }, $items),
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($items),
            ],
        ]);
    }
}
