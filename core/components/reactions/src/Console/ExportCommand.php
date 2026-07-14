<?php

namespace Reactions\Console;

use Reactions\Model\Reaction;
use Reactions\Model\ReactionType;

class ExportCommand extends AbstractCommand
{
    public function execute(): int
    {
        $criteria = $this->buildCriteria();
        $types = $this->loadTypeMap();
        $items = [];

        foreach ($this->modx->getCollection(Reaction::class, $criteria) as $reaction) {
            $typeId = (int) $reaction->get('type_id');
            $items[] = [
                'id' => (int) $reaction->get('id'),
                'class_key' => $reaction->get('object_class'),
                'object_id' => (int) $reaction->get('object_id'),
                'context' => $reaction->get('context'),
                'type_id' => $typeId,
                'type' => $types[$typeId] ?? null,
                'user_id' => $reaction->get('user_id') !== null ? (int) $reaction->get('user_id') : null,
                'fingerprint' => $reaction->get('fingerprint'),
                'created_at' => (int) $reaction->get('created_at'),
                'updated_at' => (int) $reaction->get('updated_at'),
            ];
        }

        $payload = [
            'exported_at' => time(),
            'count' => count($items),
            'items' => $items,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $file = $this->getStringOption('file');

        if ($file !== '') {
            if (file_put_contents($file, $json . PHP_EOL) === false) {
                $this->writelnError("Failed to write file: {$file}");

                return 1;
            }

            $this->writeln('Exported ' . count($items) . " reaction(s) to {$file}");

            return 0;
        }

        $this->writeln($json);

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCriteria(): array
    {
        $criteria = [];

        $classKey = $this->getStringOption('class-key');
        if ($classKey !== '') {
            $criteria['object_class'] = $classKey;
        }

        $objectId = $this->getIntOption('object-id');
        if ($objectId > 0) {
            $criteria['object_id'] = $objectId;
        }

        $context = $this->getStringOption('context');
        if ($context !== '') {
            $criteria['context'] = $context;
        }

        return $criteria;
    }

    /**
     * @return array<int, array{id: int, name: string, emoji: string}>
     */
    private function loadTypeMap(): array
    {
        $map = [];
        foreach ($this->modx->getCollection(ReactionType::class) as $type) {
            $id = (int) $type->get('id');
            $map[$id] = [
                'id' => $id,
                'name' => (string) $type->get('name'),
                'emoji' => (string) $type->get('emoji'),
            ];
        }

        return $map;
    }
}
