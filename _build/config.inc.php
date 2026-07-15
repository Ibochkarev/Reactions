<?php

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') && (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

return [
    'name' => 'Reactions',
    'name_lower' => 'reactions',
    'version' => '1.0.1',
    'release' => 'pl',
    'install' => false,
    'update' => [
        'chunks' => true,
        'menus' => false,
        'permission' => true,
        'plugins' => true,
        'policies' => true,
        'policy_templates' => true,
        'resources' => false,
        'settings' => false,
        'snippets' => true,
        'templates' => false,
        'widgets' => false,
    ],
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    'log_level' => !empty($_REQUEST['download']) ? 0 : 3,
    'log_target' => php_sapi_name() == 'cli' ? 'ECHO' : 'HTML',
    'download' => !empty($_REQUEST['download']),
];
