<?php

namespace Reactions\Api\Controller;

use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\Reaction;

class LatestController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $classKey = $this->queryString('class_key');
        $context = $this->queryString('context');
        [$limit, $offset] = $this->pagination();

        $criteria = [];
        if ($classKey !== '') {
            $criteria['object_class'] = $classKey;
        }
        if ($context !== '') {
            $criteria['context'] = $context;
        }

        $query = $this->modx()->newQuery(Reaction::class, $criteria);
        $query->sortby('created_at', 'DESC');
        $query->limit($limit, $offset);
        $query->select($this->modx()->getSelectColumns(Reaction::class, 'Reaction'));
        $query->leftJoin('Reactions\\Model\\ReactionType', 'Type', 'Reaction.type_id = Type.id');
        $query->select(['Type.name AS type_name', 'Type.emoji AS type_emoji']);

        /** @var list<Reaction> $reactions */
        $reactions = $this->modx()->getCollection(Reaction::class, $query);

        JsonResponse::success([
            'data' => [
                'items' => array_map(static fn (Reaction $reaction): array => [
                    'id' => (int) $reaction->get('id'),
                    'class_key' => $reaction->get('object_class'),
                    'object_id' => (int) $reaction->get('object_id'),
                    'context' => $reaction->get('context'),
                    'type' => $reaction->get('type_name') ?? '',
                    'emoji' => $reaction->get('type_emoji') ?? '',
                    'created_at' => (int) $reaction->get('created_at'),
                ], $reactions),
                'limit' => $limit,
                'offset' => $offset,
                'total' => count($reactions),
            ],
        ]);
    }
}
