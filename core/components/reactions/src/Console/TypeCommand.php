<?php

namespace Reactions\Console;

use Reactions\Model\ReactionType;

class TypeCommand extends AbstractCommand
{
    public function execute(): int
    {
        $action = $this->getStringOption('action', 'list');

        return match ($action) {
            'create' => $this->create(),
            'list' => $this->list(),
            'remove' => $this->remove(),
            default => $this->unknownAction($action),
        };
    }

    private function create(): int
    {
        if (!$this->requireOptions(['name'])) {
            return 1;
        }

        $name = $this->getStringOption('name');
        $existing = $this->modx->getObject(ReactionType::class, ['name' => $name]);
        if ($existing !== null) {
            $this->writelnError("Type already exists: {$name}");

            return 1;
        }

        /** @var ReactionType $type */
        $type = $this->modx->newObject(ReactionType::class);
        $type->fromArray([
            'name' => $name,
            'emoji' => $this->getStringOption('emoji'),
            'icon' => $this->getOption('icon'),
            'ordering' => $this->getIntOption('ordering'),
            'active' => !$this->hasOption('inactive'),
        ], '', true, true);

        if (!$type->save()) {
            $this->writelnError('Failed to create type.');

            return 1;
        }

        $this->writeln($this->formatLine($type));

        return 0;
    }

    private function list(): int
    {
        $query = $this->modx->newQuery(ReactionType::class);
        $query->sortby('ordering', 'ASC');
        $types = $this->modx->getCollection(ReactionType::class, $query);
        if ($types === []) {
            $this->writeln('No reaction types.');

            return 0;
        }

        foreach ($types as $type) {
            $this->writeln($this->formatLine($type));
        }

        return 0;
    }

    private function remove(): int
    {
        $id = $this->getIntOption('id');
        $name = $this->getStringOption('name');

        if ($id <= 0 && $name === '') {
            $this->writelnError('Provide --id or --name.');

            return 1;
        }

        $type = $id > 0
            ? $this->modx->getObject(ReactionType::class, $id)
            : $this->modx->getObject(ReactionType::class, ['name' => $name]);

        if ($type === null) {
            $this->writelnError('Type not found.');

            return 1;
        }

        $typeId = (int) $type->get('id');
        if (!$type->remove()) {
            $this->writelnError('Failed to remove type.');

            return 1;
        }

        $this->writeln("Removed type #{$typeId}");

        return 0;
    }

    private function formatLine(ReactionType $type): string
    {
        return sprintf(
            '#%d  %s  name=%s  active=%s  ordering=%d',
            (int) $type->get('id'),
            (string) $type->get('emoji'),
            (string) $type->get('name'),
            $type->get('active') ? 'yes' : 'no',
            (int) $type->get('ordering'),
        );
    }

    private function unknownAction(string $action): int
    {
        $this->writelnError("Unknown type action: {$action}. Use create, list, or remove.");

        return 1;
    }
}
