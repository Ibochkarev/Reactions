<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array<string, mixed>    $scriptProperties
 */

use Reactions\Enum\Period;
use Reactions\Snippet\TopListSnippet;

return (new TopListSnippet($modx, 'likes', Period::All))->process($scriptProperties);
