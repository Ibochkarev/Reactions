<?php

/** @var xPDO\Transport\xPDOTransport $transport */
/** @var array $options */
/** @var MODX\Revolution\modX $modx */

use Reactions\Model\ReactionType;
use Reactions\Model\ReactionSet;
use Reactions\Model\ReactionSetType;

if ($transport->xpdo) {
    $modx = $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx->addPackage('Reactions\\Model\\', MODX_CORE_PATH . 'components/reactions/src/', null, 'Reactions\\');

            $types = [
                'like' => ['emoji' => '👍', 'ordering' => 10],
                'dislike' => ['emoji' => '👎', 'ordering' => 20],
                'love' => ['emoji' => '❤️', 'ordering' => 30],
                'funny' => ['emoji' => '😂', 'ordering' => 40],
                'wow' => ['emoji' => '😮', 'ordering' => 50],
                'sad' => ['emoji' => '😢', 'ordering' => 60],
                'angry' => ['emoji' => '😡', 'ordering' => 70],
                'hooray' => ['emoji' => '🎉', 'ordering' => 80],
            ];

            $typeIds = [];
            foreach ($types as $name => $meta) {
                $type = $modx->getObject(ReactionType::class, ['name' => $name]);
                if (!$type) {
                    $type = $modx->newObject(ReactionType::class);
                    $type->fromArray([
                        'name' => $name,
                        'emoji' => $meta['emoji'],
                        'ordering' => $meta['ordering'],
                        'active' => true,
                    ], '', true, true);
                    $type->save();
                }
                $typeIds[$name] = (int) $type->get('id');
            }

            $sets = [
                'updown' => [
                    'title' => 'Up / Down',
                    'exclusive' => true,
                    'types' => ['like', 'dislike'],
                ],
                'github' => [
                    'title' => 'GitHub',
                    'exclusive' => false,
                    'types' => ['like', 'dislike', 'love', 'funny', 'wow', 'sad', 'angry', 'hooray'],
                ],
            ];

            foreach ($sets as $key => $meta) {
                $set = $modx->getObject(ReactionSet::class, ['key' => $key]);
                if (!$set) {
                    $set = $modx->newObject(ReactionSet::class);
                    $set->fromArray([
                        'key' => $key,
                        'title' => $meta['title'],
                        'exclusive' => $meta['exclusive'],
                        'active' => true,
                    ], '', true, true);
                    $set->save();
                }

                $setId = (int) $set->get('id');
                $order = 0;
                foreach ($meta['types'] as $typeName) {
                    if (!isset($typeIds[$typeName])) {
                        continue;
                    }
                    $link = $modx->getObject(ReactionSetType::class, [
                        'set_id' => $setId,
                        'type_id' => $typeIds[$typeName],
                    ]);
                    if (!$link) {
                        $link = $modx->newObject(ReactionSetType::class);
                        $link->fromArray([
                            'set_id' => $setId,
                            'type_id' => $typeIds[$typeName],
                            'ordering' => $order,
                        ], '', true, true);
                        $link->save();
                    }
                    $order += 10;
                }
            }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}

return true;
