<?php

namespace Reactions\Console;

use Reactions\Model\Reaction;
use Reactions\Model\ReactionAggregate;

class RecountCommand extends AbstractCommand
{
    public function execute(): int
    {
        $classKey = $this->getStringOption('class-key');
        $objectId = $this->getIntOption('object-id');
        $context = $this->getStringOption('context', 'web');
        $aggregate = $this->reactions->getAggregateService();

        if ($classKey !== '' && $objectId > 0) {
            $result = $aggregate->recount($classKey, $objectId, $context);
            $this->writeln(sprintf(
                'Recounted %s#%d (%s): total=%d, likes=%d, dislikes=%d',
                $classKey,
                $objectId,
                $context,
                (int) $result->get('total'),
                (int) $result->get('likes'),
                (int) $result->get('dislikes'),
            ));

            return 0;
        }

        $targets = $this->collectTargets();
        if ($targets === []) {
            $this->writeln('No reactions or aggregates to recount.');

            return 0;
        }

        $count = 0;
        foreach ($targets as $target) {
            $aggregate->recount($target['class_key'], $target['object_id'], $target['context']);
            ++$count;
        }

        $this->writeln("Recounted {$count} object(s).");

        return 0;
    }

    /**
     * @return list<array{class_key: string, object_id: int, context: string}>
     */
    private function collectTargets(): array
    {
        $seen = [];

        foreach ($this->modx->getCollection(Reaction::class) as $reaction) {
            $key = $this->targetKey(
                (string) $reaction->get('class_key'),
                (int) $reaction->get('object_id'),
                (string) $reaction->get('context'),
            );
            $seen[$key] = [
                'class_key' => (string) $reaction->get('class_key'),
                'object_id' => (int) $reaction->get('object_id'),
                'context' => (string) $reaction->get('context'),
            ];
        }

        foreach ($this->modx->getCollection(ReactionAggregate::class) as $row) {
            $key = $this->targetKey(
                (string) $row->get('class_key'),
                (int) $row->get('object_id'),
                (string) $row->get('context'),
            );
            $seen[$key] = [
                'class_key' => (string) $row->get('class_key'),
                'object_id' => (int) $row->get('object_id'),
                'context' => (string) $row->get('context'),
            ];
        }

        return array_values($seen);
    }

    private function targetKey(string $classKey, int $objectId, string $context): string
    {
        return $classKey . ':' . $objectId . ':' . $context;
    }
}
