<?php

/** @var xPDO\Transport\xPDOTransport $transport */
/** @var array $options */
/** @var MODX\Revolution\modX $modx */

if ($transport->xpdo) {
    $modx = $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx->addPackage('Reactions\\Model\\', MODX_CORE_PATH . 'components/reactions/src/', null, 'Reactions\\');
            $manager = $modx->getManager();
            $objects = [];
            $schemaFile = MODX_CORE_PATH . 'components/reactions/schema/reactions.mysql.schema.xml';
            if (is_file($schemaFile)) {
                $schema = new SimpleXMLElement($schemaFile, 0, true);
                if (isset($schema->object)) {
                    foreach ($schema->object as $obj) {
                        $objects[] = (string) $obj['class'];
                    }
                }
                unset($schema);
            }
            foreach ($objects as $class) {
                $class = 'Reactions\\Model\\' . $class;
                $table = $modx->getTableName($class);
                $sql = "SHOW TABLES LIKE '" . trim($table, '`') . "'";
                $stmt = $modx->prepare($sql);
                $newTable = true;
                if ($stmt->execute() && $stmt->fetchAll()) {
                    $newTable = false;
                }
                if ($newTable) {
                    $manager->createObjectContainer($class);
                } else {
                    $tableFields = [];
                    $c = $modx->prepare("SHOW COLUMNS IN {$modx->getTableName($class)}");
                    $c->execute();
                    while ($cl = $c->fetch(PDO::FETCH_ASSOC)) {
                        $tableFields[$cl['Field']] = $cl['Field'];
                    }
                    foreach ($modx->getFields($class) as $field => $v) {
                        if (in_array($field, $tableFields, true)) {
                            unset($tableFields[$field]);
                            $manager->alterField($class, $field);
                        } else {
                            $manager->addField($class, $field);
                        }
                    }
                    foreach ($tableFields as $field) {
                        $manager->removeField($class, $field);
                    }
                    $indexes = [];
                    $c = $modx->prepare("SHOW INDEX FROM {$modx->getTableName($class)}");
                    $c->execute();
                    while ($row = $c->fetch(PDO::FETCH_ASSOC)) {
                        $name = $row['Key_name'];
                        if (!isset($indexes[$name])) {
                            $indexes[$name] = [$row['Column_name']];
                        } else {
                            $indexes[$name][] = $row['Column_name'];
                        }
                    }
                    foreach ($indexes as $name => $values) {
                        sort($values);
                        $indexes[$name] = implode(':', $values);
                    }
                    $map = $modx->getIndexMeta($class);
                    foreach ($indexes as $key => $index) {
                        if (!isset($map[$key])) {
                            $manager->removeIndex($class, $key);
                        }
                    }
                    foreach ($map as $key => $index) {
                        ksort($index['columns']);
                        $index = implode(':', array_keys($index['columns']));
                        if (!isset($indexes[$key])) {
                            $manager->addIndex($class, $key);
                        } elseif ($index !== $indexes[$key]) {
                            $manager->removeIndex($class, $key);
                            $manager->addIndex($class, $key);
                        }
                    }
                }
            }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            break;
    }
}

return true;
