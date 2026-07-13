<?php

namespace Reactions\Model\mysql;

class ReactionType extends \Reactions\Model\ReactionType
{
    public static $metaMap = array (
        'package' => 'Reactions\\Model',
        'version' => '3.0',
        'table' => 'reactions_types',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' =>
        array (
            'engine' => 'InnoDB',
        ),
        'fields' =>
        array (
            'name' => '',
            'emoji' => '',
            'icon' => null,
            'ordering' => 0,
            'active' => 1,
        ),
        'fieldMeta' =>
        array (
            'name' =>
            array (
                'dbtype' => 'varchar',
                'precision' => '64',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'emoji' =>
            array (
                'dbtype' => 'varchar',
                'precision' => '16',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'icon' =>
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => true,
                'default' => '',
            ),
            'ordering' =>
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'active' =>
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 1,
            ),
        ),
        'indexes' =>
        array (
            'name' =>
            array (
                'alias' => 'name',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' =>
                array (
                    'name' =>
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'active_ordering' =>
            array (
                'alias' => 'active_ordering',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' =>
                array (
                    'active' =>
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'ordering' =>
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );
}
