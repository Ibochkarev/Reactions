<?php

return [
    'default_set' => [
        'xtype' => 'textfield',
        'value' => 'updown',
        'area' => 'reactions_main',
    ],
    'identity_strategy' => [
        'xtype' => 'textfield',
        'value' => 'ip_cookie',
        'area' => 'reactions_main',
    ],
    'allow_multiple' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'reactions_main',
    ],
    'rate_limit' => [
        'xtype' => 'numberfield',
        'value' => 10,
        'area' => 'reactions_security',
    ],
    'rate_limit_window' => [
        'xtype' => 'numberfield',
        'value' => 60,
        'area' => 'reactions_security',
    ],
    'cache_handler' => [
        'xtype' => 'textfield',
        'value' => 'modx',
        'area' => 'reactions_main',
    ],
    'webhooks_enabled' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'reactions_integrations',
    ],
    'webhook_url' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'reactions_integrations',
    ],
    'notify_authors' => [
        'xtype' => 'combo-boolean',
        'value' => false,
        'area' => 'reactions_integrations',
    ],
    'block_bots' => [
        'xtype' => 'combo-boolean',
        'value' => true,
        'area' => 'reactions_security',
    ],
    'allowed_classes' => [
        'xtype' => 'textfield',
        'value' => 'modResource,msProduct,TicketComment',
        'area' => 'reactions_security',
    ],
];
