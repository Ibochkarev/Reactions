<?php

namespace Reactions\Api\Controller\Admin;

use Reactions\Api\Controller\AbstractController;
use Reactions\Api\JsonResponse;
use Reactions\Exception\ReactionException;
use Reactions\Model\ReactionBan;

class BansController extends AbstractController
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
        $bans = $this->modx()->getCollection(ReactionBan::class);
        $items = array_map([$this, 'formatBan'], $bans);

        JsonResponse::success(['data' => ['items' => $items]]);
    }

    private function save(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id');

        /** @var ReactionBan|null $ban */
        $ban = $id > 0
            ? $this->modx()->getObject(ReactionBan::class, $id)
            : $this->modx()->newObject(ReactionBan::class);

        if ($ban === null) {
            throw new ReactionException('Ban not found', 404, 'not_found');
        }

        if (array_key_exists('ip', $body) && is_string($body['ip']) && $body['ip'] !== '') {
            $ban->set('ip_hash', hash('sha256', $body['ip']));
        } elseif (array_key_exists('ip_hash', $body)) {
            $ban->set('ip_hash', $body['ip_hash'] !== null ? (string) $body['ip_hash'] : null);
        }

        if (array_key_exists('user_id', $body)) {
            $ban->set('user_id', $body['user_id'] !== null ? (int) $body['user_id'] : null);
        }

        if (array_key_exists('reason', $body)) {
            $ban->set('reason', $body['reason'] !== null ? (string) $body['reason'] : null);
        }

        if (array_key_exists('expires_at', $body)) {
            $ban->set('expires_at', $body['expires_at'] !== null ? (int) $body['expires_at'] : null);
        }

        if ((int) $ban->get('id') <= 0) {
            $ban->set('created_at', time());
        }

        if ($ban->get('ip_hash') === null && $ban->get('user_id') === null) {
            throw new ReactionException('ip or user_id is required', 400, 'validation_error');
        }

        if (!$ban->save()) {
            throw new ReactionException('Failed to save ban', 500, 'save_failed');
        }

        JsonResponse::success(['data' => $this->formatBan($ban)]);
    }

    private function remove(): void
    {
        $body = $this->jsonBody();
        $id = $this->bodyInt($body, 'id', $this->queryInt('id'));

        if ($id <= 0) {
            throw new ReactionException('id is required', 400, 'validation_error');
        }

        $ban = $this->modx()->getObject(ReactionBan::class, $id);
        if ($ban === null || !$ban->remove()) {
            throw new ReactionException('Ban not found', 404, 'not_found');
        }

        JsonResponse::success(['data' => ['id' => $id, 'removed' => true]]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatBan(ReactionBan $ban): array
    {
        return [
            'id' => (int) $ban->get('id'),
            'ip_hash' => $ban->get('ip_hash'),
            'user_id' => $ban->get('user_id') !== null ? (int) $ban->get('user_id') : null,
            'reason' => $ban->get('reason'),
            'created_at' => (int) $ban->get('created_at'),
            'expires_at' => $ban->get('expires_at') !== null ? (int) $ban->get('expires_at') : null,
        ];
    }
}
