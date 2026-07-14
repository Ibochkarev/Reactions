<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */

/**
 * Dev resolver: point the MODX install at Extras/Reactions sources.
 * Extra repo keeps real directories (git-friendly); site uses symlinks.
 */
if ($transport->xpdo) {
    $modx = $transport->xpdo;
    $dev = MODX_BASE_PATH . 'Extras/Reactions/';
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        $devCore = $dev . 'core/components/reactions';
        $devAssets = $dev . 'assets/components/reactions';
        $modxCore = MODX_CORE_PATH . 'components/reactions';
        $modxAssets = MODX_ASSETS_PATH . 'components/reactions';

        if (is_dir($devCore) && !is_link($devCore) && !is_link($modxCore)) {
            $cache->deleteTree(
                $modxCore,
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink($devCore, $modxCore);
        }
        if (is_dir($devAssets) && !is_link($devAssets) && !is_link($modxAssets)) {
            $cache->deleteTree(
                $modxAssets,
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink($devAssets, $modxAssets);
        }
    }
}

return true;
