<?php
namespace Reactions\Model\mysql;

use xPDO\xPDO;

class ReactionSet extends \Reactions\Model\ReactionSet
{

    public static $metaMap = array (
        'package' => 'Reactions\\Model',
        'version' => '3.0',
        'table' => 'reactions_sets',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'key' => '',
            'title' => '',
            'exclusive' => 1,
            'active' => 1,
        ),
        'fieldMeta' => 
        array (
            'key' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '64',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'title' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'exclusive' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 1,
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
            'key' => 
            array (
                'alias' => 'key',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
        'composites' => 
        array (
            'SetTypes' => 
            array (
                'class' => 'Reactions\\Model\\ReactionSetType',
                'local' => 'id',
                'foreign' => 'set_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );

}
