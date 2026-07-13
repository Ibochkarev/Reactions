<?php

namespace Reactions\Model\mysql;

class ReactionBan extends \Reactions\Model\ReactionBan
{
    public static $metaMap = array (
        'package' => 'Reactions\\Model',
        'version' => '3.0',
        'table' => 'reactions_bans',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' =>
        array (
            'engine' => 'InnoDB',
        ),
        'fields' =>
        array (
            'ip_hash' => null,
            'user_id' => null,
            'reason' => '',
            'created_at' => 0,
            'expires_at' => null,
        ),
        'fieldMeta' =>
        array (
            'ip_hash' =>
            array (
                'dbtype' => 'varchar',
                'precision' => '64',
                'phptype' => 'string',
                'null' => true,
            ),
            'user_id' =>
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => true,
            ),
            'reason' =>
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => true,
                'default' => '',
            ),
            'created_at' =>
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'expires_at' =>
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => true,
            ),
        ),
        'indexes' =>
        array (
            'ip_hash' =>
            array (
                'alias' => 'ip_hash',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' =>
                array (
                    'ip_hash' =>
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => true,
                    ),
                ),
            ),
            'user_id' =>
            array (
                'alias' => 'user_id',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' =>
                array (
                    'user_id' =>
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => true,
                    ),
                ),
            ),
        ),
    );
}
