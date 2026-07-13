<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array<string, mixed>    $scriptProperties
 */

use Reactions\Snippet\ReactionsCountSnippet;

return (new ReactionsCountSnippet($modx))->process($scriptProperties);
