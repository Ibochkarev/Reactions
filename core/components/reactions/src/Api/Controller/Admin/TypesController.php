<?php

namespace Reactions\Api\Controller\Admin;

use Reactions\Api\Controller\AbstractController;
use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\ReactionType;

class TypesController extends AbstractController
{
    public function handle(string $method): void
    {
        if ($method !== 'GET') {
            $this->guardMutation();
        }

        match ($method) {
            'GET' => $this->list(),
            'POST' => $this->save(),
            'DELETE' => $this->remove(),
            default => throw new ReactionException('Method not allowed', 405, 'method_not_allowed'),
        };
    }

    private function list(): void
    {
        $types = $this->modx()->getCollection(ReactionType::class);
        $items = array_map([$this, 'formatType'], $types);

        JsonResponse::success(['data' => ['items' => $items]]);
    }

    private function save(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id');

        /** @var ReactionType $type */
        $type = $id > 0
            ? $this->modx()->getObject(ReactionType::class, $id)
            : $this->modx()->newObject(ReactionType::class);

        if ($type === null) {
            throw new ReactionException('Type not found', 404, 'not_found');
        }

        $name = $this->bodyString($body, 'name');
        if ($name !== '') {
            $type->set('name', $name);
        }

        $emoji = $this->bodyString($body, 'emoji');
        if ($emoji !== '') {
            $type->set('emoji', $emoji);
        }

        if (array_key_exists('icon', $body)) {
            $type->set('icon', $body['icon'] !== null ? (string) $body['icon'] : null);
        }

        if (array_key_exists('ordering', $body)) {
            $type->set('ordering', (int) $body['ordering']);
        }

        if (array_key_exists('active', $body)) {
            $type->set('active', (bool) $body['active']);
        }

        if (!$type->save()) {
            throw new ReactionException('Failed to save type', 500, 'save_failed');
        }

        JsonResponse::success(['data' => $this->formatType($type)]);
    }

    private function remove(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id', $this->queryInt('id'));

        if ($id <= 0) {
            throw new ReactionException('id is required', 400, 'validation_error');
        }

        $type = $this->modx()->getObject(ReactionType::class, $id);
        if ($type === null || !$type->remove()) {
            throw new ReactionException('Type not found', 404, 'not_found');
        }

        JsonResponse::success(['data' => ['id' => $id, 'removed' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatType(ReactionType $type): array
    {
        return [
            'id' => (int) $type->get('id'),
            'name' => $type->get('name'),
            'emoji' => $type->get('emoji'),
            'icon' => $type->get('icon'),
            'ordering' => (int) $type->get('ordering'),
            'active' => (bool) $type->get('active'),
        ];
    }
}
