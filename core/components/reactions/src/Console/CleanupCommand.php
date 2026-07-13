<?php

namespace Reactions\Console;

use Reactions\Model\Reaction;
use Reactions\Model\ReactionBan;
use Reactions\Model\ReactionType;

class CleanupCommand extends AbstractCommand
{
    public function execute(): int
    {
        $removedOrphans = 0;
        $removedBans = 0;

        if ($this->getBoolOption('orphans')) {
            $removedOrphans = $this->removeOrphanedReactions();
            $this->writeln("Removed {$removedOrphans} orphaned reaction(s).");
        }

        $removedBans = $this->removeExpiredBans();
        $this->writeln("Removed {$removedBans} expired ban(s).");

        if (!$this->getBoolOption('orphans') && $removedBans === 0) {
            $this->writeln('Nothing to clean. Use --orphans to remove reactions with missing types.');
        }

        return 0;
    }

    private function removeOrphanedReactions(): int
    {
        $typeIds = [];
        foreach ($this->modx->getCollection(ReactionType::class) as $type) {
            $typeIds[(int) $type->get('id')] = true;
        }

        $removed = 0;
        foreach ($this->modx->getCollection(Reaction::class) as $reaction) {
            $typeId = (int) $reaction->get('type_id');
            if (isset($typeIds[$typeId])) {
                continue;
            }

            if ($reaction->remove()) {
                ++$removed;
            }
        }

        return $removed;
    }

    private function removeExpiredBans(): int
    {
        $now = time();
        $removed = 0;

        foreach ($this->modx->getCollection(ReactionBan::class, ['expires_at:<=' => $now]) as $ban) {
            $expiresAt = $ban->get('expires_at');
            if ($expiresAt === null || (int) $expiresAt <= 0) {
                continue;
            }

            if ($ban->remove()) {
                ++$removed;
            }
        }

        return $removed;
    }
}
