<?php

header('Content-Type: application/json; charset=utf-8');

define('MODX_API_MODE', true);

require_once dirname(__DIR__, 3) . '/index.php';

$modx->getRequest();
$modx->initialize('web');

if (!$modx->services->has('Reactions')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Reactions service is not registered',
        'code' => 'service_missing',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/** @var \Reactions\Reactions $reactions */
$reactions = $modx->services->get('Reactions');

$router = new \Reactions\Api\Router($reactions);
$router->handle();
