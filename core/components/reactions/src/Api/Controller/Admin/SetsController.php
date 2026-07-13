<?php

namespace Reactions\Api\Controller\Admin;

use Reactions\Api\Controller\AbstractController;
use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\ReactionSet;
use Reactions\Model\ReactionSetType;
use Reactions\Model\ReactionType;

class SetsController extends AbstractController
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
        $sets = $this->modx()->getCollection(ReactionSet::class);
        $items = array_map([$this, 'formatSet'], $sets);

        JsonResponse::success(['data' => ['items' => $items]]);
    }

    private function save(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id');

        /** @var ReactionSet|null $set */
        $set = $id > 0
            ? $this->modx()->getObject(ReactionSet::class, $id)
            : $this->modx()->newObject(ReactionSet::class);

        if ($set === null) {
            throw new ReactionException('Set not found', 404, 'not_found');
        }

        $key = $this->bodyString($body, 'key');
        if ($key !== '') {
            $set->set('key', $key);
        }

        $title = $this->bodyString($body, 'title');
        if ($title !== '') {
            $set->set('title', $title);
        }

        if (array_key_exists('exclusive', $body)) {
            $set->set('exclusive', (bool) $body['exclusive']);
        }

        if (array_key_exists('active', $body)) {
            $set->set('active', (bool) $body['active']);
        }

        if (!$set->save()) {
            throw new ReactionException('Failed to save set', 500, 'save_failed');
        }

        if (isset($body['types']) && is_array($body['types'])) {
            $this->syncTypes($set, $body['types']);
        }

        JsonResponse::success(['data' => $this->formatSet($set)]);
    }

    /**
     * @param list<int|string> $typeNamesOrIds
     */
    private function syncTypes(ReactionSet $set, array $typeNamesOrIds): void
    {
        $setId = (int) $set->get('id');
        $existing = $this->modx()->getCollection(ReactionSetType::class, ['set_id' => $setId]);
        foreach ($existing as $row) {
            $row->remove();
        }

        $ordering = 0;
        foreach ($typeNamesOrIds as $entry) {
            $type = is_numeric($entry)
                ? $this->modx()->getObject(ReactionType::class, (int) $entry)
                : $this->modx()->getObject(ReactionType::class, ['name' => (string) $entry]);

            if ($type === null) {
                continue;
            }

            /** @var ReactionSetType $link */
            $link = $this->modx()->newObject(ReactionSetType::class);
            $link->fromArray([
                'set_id' => $setId,
                'type_id' => (int) $type->get('id'),
                'ordering' => $ordering++,
            ]);
            $link->save();
        }
    }

    private function remove(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id', $this->queryInt('id'));

        if ($id <= 0) {
            throw new ReactionException('id is required', 400, 'validation_error');
        }

        $set = $this->modx()->getObject(ReactionSet::class, $id);
        if ($set === null || !$set->remove()) {
            throw new ReactionException('Set not found', 404, 'not_found');
        }

        JsonResponse::success(['data' => ['id' => $id, 'removed' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSet(ReactionSet $set): array
    {
        $setId = (int) $set->get('id');
        $links = $this->modx()->getCollection(ReactionSetType::class, ['set_id' => $setId]);
        $types = [];

        foreach ($links as $link) {
            $type = $this->modx()->getObject(ReactionType::class, (int) $link->get('type_id'));
            if ($type !== null) {
                $types[] = [
                    'id' => (int) $type->get('id'),
                    'name' => $type->get('name'),
                    'emoji' => $type->get('emoji'),
                    'ordering' => (int) $link->get('ordering'),
                ];
            }
        }

        usort($types, static fn (array $a, array $b): int => $a['ordering'] <=> $b['ordering']);

        return [
            'id' => $setId,
            'key' => $set->get('key'),
            'title' => $set->get('title'),
            'exclusive' => (bool) $set->get('exclusive'),
            'active' => (bool) $set->get('active'),
            'types' => $types,
        ];
    }
}
