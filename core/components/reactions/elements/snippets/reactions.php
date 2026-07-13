<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array<string, mixed>    $scriptProperties
 */

use Reactions\Snippet\ReactionsSnippet;

return (new ReactionsSnippet($modx))->process($scriptProperties);
