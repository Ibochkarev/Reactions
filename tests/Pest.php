<?php

require_once __DIR__ . '/../vendor/autoload.php';

uses()->afterEach(function () {
    \Mockery::close();
})->in('Unit', 'Integration');
