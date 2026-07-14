<?php

namespace Reactions\Api\Controller;

use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Support\Counts;

class CountsController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $classKey = $this->queryString('class_key');
        $objectId = $this->queryInt('object_id');
        $context = $this->queryString('context', 'web');

        if ($classKey === '' || $objectId <= 0) {
            throw new ReactionException('class_key and object_id are required', 400, 'validation_error');
        }

        $aggregate = $this->reactions->getAggregateService();
        $identity = $this->reactions->getIdentityResolver()->resolve($this->reactions);
        $counts = $aggregate->getCounts($classKey, $objectId, $context);
        $userReactions = $aggregate->getUserReactions($classKey, $objectId, $context, $identity);

        JsonResponse::success([
            'data' => [
                'class_key' => $classKey,
                'object_id' => $objectId,
                'context' => $context,
                'counts' => $counts,
                'total' => Counts::total($counts),
                'user_reaction' => $userReactions,
            ],
        ]);
    }
}
