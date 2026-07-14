<?php

declare(strict_types=1);

define('MODX_API_MODE', true);

$modxIndex = resolveReactionsModxIndex();
if ($modxIndex === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'MODX index.php not found (symlink-safe bootstrap failed)',
        'code' => 'modx_bootstrap',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once $modxIndex;

/** @var \MODX\Revolution\modX $modx */
$modx->getRequest();
$modx->initialize('web');

if (!$modx->services->has('Reactions')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
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

/**
 * Find MODX index.php when assets/components/reactions is a symlink into Extras/.
 *
 * @return non-empty-string|null
 */
function resolveReactionsModxIndex(): ?string
{
    $candidates = [];

    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if (is_string($docRoot) && $docRoot !== '') {
        $candidates[] = rtrim(str_replace('\\', '/', $docRoot), '/') . '/index.php';
    }

    $script = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if (is_string($script) && $script !== '') {
        $candidates[] = dirname(str_replace('\\', '/', $script), 3) . '/index.php';
    }

    // Extra layout: …/Extras/Reactions/assets/components/reactions → site is 5 levels up.
    $dir = str_replace('\\', '/', __DIR__);
    for ($i = 3; $i <= 6; ++$i) {
        $candidates[] = dirname($dir, $i) . '/index.php';
    }

    $cwd = getcwd();
    if (is_string($cwd) && $cwd !== '') {
        $walk = str_replace('\\', '/', $cwd);
        for ($i = 0; $i < 8; ++$i) {
            $candidates[] = $walk . '/index.php';
            $parent = dirname($walk);
            if ($parent === $walk) {
                break;
            }
            $walk = $parent;
        }
    }

    foreach ($candidates as $index) {
        if (!is_string($index) || $index === '') {
            continue;
        }
        $index = str_replace('\\', '/', $index);
        $root = dirname($index);
        if (is_file($index) && is_file($root . '/core/config/config.inc.php')) {
            return $index;
        }
    }

    return null;
}
