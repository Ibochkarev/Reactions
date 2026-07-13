<?php

namespace Reactions\Console;

use Reactions\Model\ReactionSet;
use Reactions\Model\ReactionSetType;
use Reactions\Model\ReactionType;

class SetCommand extends AbstractCommand
{
    public function execute(): int
    {
        $action = $this->getStringOption('action', 'list');

        return match ($action) {
            'create' => $this->create(),
            'list' => $this->list(),
            'attach' => $this->attach(),
            'remove' => $this->remove(),
            default => $this->unknownAction($action),
        };
    }

    private function create(): int
    {
        if (!$this->requireOptions(['key'])) {
            return 1;
        }

        $key = $this->getStringOption('key');
        $existing = $this->modx->getObject(ReactionSet::class, ['key' => $key]);
        if ($existing !== null) {
            $this->writelnError("Set already exists: {$key}");

            return 1;
        }

        /** @var ReactionSet $set */
        $set = $this->modx->newObject(ReactionSet::class);
        $set->fromArray([
            'key' => $key,
            'title' => $this->getStringOption('title', $key),
            'exclusive' => !$this->hasOption('non-exclusive'),
            'active' => !$this->hasOption('inactive'),
        ], '', true, true);

        if (!$set->save()) {
            $this->writelnError('Failed to create set.');

            return 1;
        }

        $types = $this->getStringOption('types');
        if ($types !== '') {
            $this->syncTypes($set, $this->parseTypeList($types));
        }

        $this->writeln($this->formatLine($set));

        return 0;
    }

    private function list(): int
    {
        $sets = $this->modx->getCollection(ReactionSet::class);
        if ($sets === []) {
            $this->writeln('No reaction sets.');

            return 0;
        }

        foreach ($sets as $set) {
            $this->writeln($this->formatLine($set));
        }

        return 0;
    }

    private function attach(): int
    {
        $set = $this->resolveSet();
        if ($set === null) {
            return 1;
        }

        $types = $this->getStringOption('types');
        if ($types === '') {
            $this->writelnError('Provide --types=like,dislike or type names.');

            return 1;
        }

        $this->syncTypes($set, $this->parseTypeList($types), $this->getBoolOption('replace', false));
        $this->writeln($this->formatLine($set));

        return 0;
    }

    private function remove(): int
    {
        $set = $this->resolveSet();
        if ($set === null) {
            return 1;
        }

        $id = (int) $set->get('id');
        if (!$set->remove()) {
            $this->writelnError('Failed to remove set.');

            return 1;
        }

        $this->writeln("Removed set #{$id}");

        return 0;
    }

    private function resolveSet(): ?ReactionSet
    {
        $id = $this->getIntOption('id');
        $key = $this->getStringOption('key');

        if ($id <= 0 && $key === '') {
            $this->writelnError('Provide --id or --key.');

            return null;
        }

        $set = $id > 0
            ? $this->modx->getObject(ReactionSet::class, $id)
            : $this->modx->getObject(ReactionSet::class, ['key' => $key]);

        if ($set === null) {
            $this->writelnError('Set not found.');

            return null;
        }

        return $set;
    }

    /**
     * @return list<string>
     */
    private function parseTypeList(string $raw): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn (string $v): bool => $v !== ''));
    }

    /**
     * @param list<string> $typeNamesOrIds
     */
    private function syncTypes(ReactionSet $set, array $typeNamesOrIds, bool $replace = true): void
    {
        $setId = (int) $set->get('id');

        if ($replace) {
            foreach ($this->modx->getCollection(ReactionSetType::class, ['set_id' => $setId]) as $row) {
                $row->remove();
            }
        }

        $ordering = $replace ? 0 : count($this->modx->getCollection(ReactionSetType::class, ['set_id' => $setId]));

        foreach ($typeNamesOrIds as $entry) {
            $type = is_numeric($entry)
                ? $this->modx->getObject(ReactionType::class, (int) $entry)
                : $this->modx->getObject(ReactionType::class, ['name' => $entry]);

            if ($type === null) {
                $this->writelnError("Type not found: {$entry}");
                continue;
            }

            /** @var ReactionSetType $link */
            $link = $this->modx->newObject(ReactionSetType::class);
            $link->fromArray([
                'set_id' => $setId,
                'type_id' => (int) $type->get('id'),
                'ordering' => $ordering++,
            ]);
            $link->save();
        }
    }

    private function formatLine(ReactionSet $set): string
    {
        $setId = (int) $set->get('id');
        $typeNames = [];

        foreach ($this->modx->getCollection(ReactionSetType::class, ['set_id' => $setId], false, true, ['ordering' => 'ASC']) as $link) {
            $type = $this->modx->getObject(ReactionType::class, (int) $link->get('type_id'));
            if ($type !== null) {
                $typeNames[] = (string) $type->get('name');
            }
        }

        return sprintf(
            '#%d  key=%s  title=%s  exclusive=%s  active=%s  types=[%s]',
            $setId,
            (string) $set->get('key'),
            (string) $set->get('title'),
            $set->get('exclusive') ? 'yes' : 'no',
            $set->get('active') ? 'yes' : 'no',
            implode(', ', $typeNames),
        );
    }

    private function unknownAction(string $action): int
    {
        $this->writelnError("Unknown set action: {$action}. Use create, list, attach, or remove.");

        return 1;
    }
}
