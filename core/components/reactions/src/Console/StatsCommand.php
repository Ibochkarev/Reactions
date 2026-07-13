<?php

namespace Reactions\Console;

use Reactions\Model\Reaction;
use Reactions\Model\ReactionAggregate;
use Reactions\Model\ReactionBan;
use Reactions\Model\ReactionType;

class StatsCommand extends AbstractCommand
{
    public function execute(): int
    {
        $todayStart = strtotime('today');
        $limit = max(1, $this->getIntOption('limit', 10));

        $this->writeln('Totals:');
        $this->writeln('  reactions:  ' . $this->modx->getCount(Reaction::class));
        $this->writeln('  aggregates: ' . $this->modx->getCount(ReactionAggregate::class));
        $this->writeln('  types:      ' . $this->modx->getCount(ReactionType::class));
        $this->writeln('  bans:       ' . $this->modx->getCount(ReactionBan::class));
        $this->writeln('  today:      ' . $this->modx->getCount(Reaction::class, ['created_at:>' => $todayStart]));
        $this->writeln('');

        $this->printTop('Top by likes', 'likes', $limit);
        $this->writeln('');
        $this->printTop('Top by total', 'total', $limit);
        $this->writeln('');
        $this->printTop('Top by trending', 'trending_score', $limit);

        return 0;
    }

    private function printTop(string $title, string $field, int $limit): void
    {
        $this->writeln($title . ':');

        $query = $this->modx->newQuery(ReactionAggregate::class);
        $query->sortby($field, 'DESC');
        $query->limit($limit);

        $rows = $this->modx->getCollection(ReactionAggregate::class, $query);
        if ($rows === []) {
            $this->writeln('  (none)');

            return;
        }

        foreach ($rows as $row) {
            $this->writeln(sprintf(
                '  %s#%d (%s)  likes=%d  total=%d  trending=%.2f',
                $row->get('class_key'),
                (int) $row->get('object_id'),
                $row->get('context'),
                (int) $row->get('likes'),
                (int) $row->get('total'),
                (float) $row->get('trending_score'),
            ));
        }
    }
}
