<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

$componentPath = $namespace['path'];
$vendorPath = $componentPath . 'vendor/';

if (file_exists($vendorPath . 'autoload.php')) {
    require_once $vendorPath . 'autoload.php';
}

$modx->addPackage('Reactions\\Model\\', $componentPath . 'src/', null, 'Reactions\\');

$modx->services->add('Reactions', function ($c) use ($modx) {
    return new \Reactions\Reactions($modx);
});
