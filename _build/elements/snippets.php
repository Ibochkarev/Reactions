<?php

return [
    'Reactions' => [
        'file' => 'reactions',
        'description' => 'Renders reaction buttons with counts for any MODX object.',
        'properties' => [
            'set' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'types' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'object' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'tpl' => [
                'type' => 'textfield',
                'value' => 'tpl.Reactions',
            ],
            'tplOuter' => [
                'type' => 'textfield',
                'value' => 'tpl.Reactions.outer',
            ],
            'toPlaceholder' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'context' => [
                'type' => 'textfield',
                'value' => '',
            ],
        ],
    ],
    'ReactionsCount' => [
        'file' => 'reactionscount',
        'description' => 'Outputs reaction counts without interactive buttons.',
        'properties' => [
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'object' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'type' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'format' => [
                'type' => 'textfield',
                'value' => '{TOTAL}',
            ],
            'toPlaceholder' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'context' => [
                'type' => 'textfield',
                'value' => '',
            ],
        ],
    ],
    'TopLiked' => [
        'file' => 'topliked',
        'description' => 'Lists objects with the most reactions for a period.',
        'properties' => [
            'period' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'day', 'value' => 'day'],
                    ['text' => 'week', 'value' => 'week'],
                    ['text' => 'month', 'value' => 'month'],
                    ['text' => 'year', 'value' => 'year'],
                    ['text' => 'all', 'value' => 'all'],
                ],
                'value' => 'all',
            ],
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'limit' => [
                'type' => 'numberfield',
                'value' => 10,
            ],
            'tpl' => [
                'type' => 'textfield',
                'value' => 'tpl.Reactions.top',
            ],
            'toPlaceholder' => [
                'type' => 'textfield',
                'value' => '',
            ],
        ],
    ],
    'TopRated' => [
        'file' => 'toprated',
        'description' => 'Lists objects sorted by rating balance (up minus down).',
        'properties' => [
            'period' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'day', 'value' => 'day'],
                    ['text' => 'week', 'value' => 'week'],
                    ['text' => 'month', 'value' => 'month'],
                    ['text' => 'year', 'value' => 'year'],
                    ['text' => 'all', 'value' => 'all'],
                ],
                'value' => 'all',
            ],
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'limit' => [
                'type' => 'numberfield',
                'value' => 10,
            ],
            'tpl' => [
                'type' => 'textfield',
                'value' => 'tpl.Reactions.top',
            ],
            'toPlaceholder' => [
                'type' => 'textfield',
                'value' => '',
            ],
        ],
    ],
    'Trending' => [
        'file' => 'trending',
        'description' => 'Lists hot objects by Reddit-style trending score.',
        'properties' => [
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'limit' => [
                'type' => 'numberfield',
                'value' => 10,
            ],
            'tpl' => [
                'type' => 'textfield',
                'value' => 'tpl.Reactions.top',
            ],
            'toPlaceholder' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'period' => [
                'type' => 'list',
                'options' => [
                    ['text' => 'day', 'value' => 'day'],
                    ['text' => 'week', 'value' => 'week'],
                    ['text' => 'month', 'value' => 'month'],
                    ['text' => 'year', 'value' => 'year'],
                    ['text' => 'all', 'value' => 'all'],
                ],
                'value' => 'all',
            ],
        ],
    ],
    'ReactionsSchema' => [
        'file' => 'reactionsschema',
        'description' => 'Outputs Schema.org AggregateRating JSON-LD for an object.',
        'properties' => [
            'class' => [
                'type' => 'textfield',
                'value' => 'modResource',
            ],
            'object' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'name' => [
                'type' => 'textfield',
                'value' => '',
            ],
            'context' => [
                'type' => 'textfield',
                'value' => '',
            ],
        ],
    ],
];
