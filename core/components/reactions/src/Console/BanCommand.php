<?php

namespace Reactions\Console;

use Reactions\Model\ReactionBan;

class BanCommand extends AbstractCommand
{
    public function execute(): int
    {
        $action = $this->getStringOption('action', 'list');

        return match ($action) {
            'add' => $this->add(),
            'remove' => $this->remove(),
            'list' => $this->list(),
            default => $this->unknownAction($action),
        };
    }

    private function add(): int
    {
        $ip = $this->getStringOption('ip');
        $userId = $this->getIntOption('user');

        if ($ip === '' && $userId <= 0) {
            $this->writelnError('Provide --ip or --user.');

            return 1;
        }

        /** @var ReactionBan $ban */
        $ban = $this->modx->newObject(ReactionBan::class);
        $ban->fromArray([
            'ip_hash' => $ip !== '' ? hash('sha256', $ip) : null,
            'user_id' => $userId > 0 ? $userId : null,
            'reason' => $this->getStringOption('reason') ?: null,
            'created_at' => time(),
            'expires_at' => $this->parseExpiresAt(),
        ], '', true, true);

        if (!$ban->save()) {
            $this->writelnError('Failed to add ban.');

            return 1;
        }

        $this->writeln($this->formatLine($ban));

        return 0;
    }

    private function remove(): int
    {
        $id = $this->getIntOption('id');
        $ip = $this->getStringOption('ip');
        $userId = $this->getIntOption('user');

        if ($id > 0) {
            $ban = $this->modx->getObject(ReactionBan::class, $id);
        } elseif ($ip !== '') {
            $ban = $this->modx->getObject(ReactionBan::class, ['ip_hash' => hash('sha256', $ip)]);
        } elseif ($userId > 0) {
            $ban = $this->modx->getObject(ReactionBan::class, ['user_id' => $userId]);
        } else {
            $this->writelnError('Provide --id, --ip, or --user.');

            return 1;
        }

        if ($ban === null) {
            $this->writelnError('Ban not found.');

            return 1;
        }

        $banId = (int) $ban->get('id');
        if (!$ban->remove()) {
            $this->writelnError('Failed to remove ban.');

            return 1;
        }

        $this->writeln("Removed ban #{$banId}");

        return 0;
    }

    private function list(): int
    {
        $query = $this->modx->newQuery(ReactionBan::class);
        $query->sortby('created_at', 'DESC');
        $bans = $this->modx->getCollection(ReactionBan::class, $query);
        if ($bans === []) {
            $this->writeln('No bans.');

            return 0;
        }

        foreach ($bans as $ban) {
            $this->writeln($this->formatLine($ban));
        }

        return 0;
    }

    private function parseExpiresAt(): ?int
    {
        $expires = $this->getStringOption('expires');
        if ($expires === '') {
            $days = $this->getIntOption('days');
            if ($days > 0) {
                return time() + ($days * 86400);
            }

            return null;
        }

        if (is_numeric($expires)) {
            return (int) $expires;
        }

        $parsed = strtotime($expires);

        return $parsed !== false ? $parsed : null;
    }

    private function formatLine(ReactionBan $ban): string
    {
        $expiresAt = $ban->get('expires_at');

        return sprintf(
            '#%d  user_id=%s  ip_hash=%s  reason=%s  created=%s  expires=%s',
            (int) $ban->get('id'),
            $ban->get('user_id') !== null ? (string) (int) $ban->get('user_id') : '-',
            $ban->get('ip_hash') !== null ? substr((string) $ban->get('ip_hash'), 0, 12) . '…' : '-',
            $ban->get('reason') !== null ? (string) $ban->get('reason') : '-',
            date('Y-m-d H:i:s', (int) $ban->get('created_at')),
            $expiresAt !== null ? date('Y-m-d H:i:s', (int) $expiresAt) : 'never',
        );
    }

    private function unknownAction(string $action): int
    {
        $this->writelnError("Unknown ban action: {$action}. Use add, remove, or list.");

        return 1;
    }
}
