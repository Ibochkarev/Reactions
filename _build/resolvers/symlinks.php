<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

if ($transport->xpdo) {
    $modx = $transport->xpdo;
    $dev = MODX_BASE_PATH . 'Extras/Reactions/';
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/reactions')) {
            $cache->deleteTree(
                $dev . 'assets/components/reactions/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/reactions/', $dev . 'assets/components/reactions');
        }
        if (!is_link($dev . 'core/components/reactions')) {
            $cache->deleteTree(
                $dev . 'core/components/reactions/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/reactions/', $dev . 'core/components/reactions');
        }
    }
}

return true;
