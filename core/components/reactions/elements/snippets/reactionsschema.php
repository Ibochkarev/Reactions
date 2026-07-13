<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array<string, mixed>    $scriptProperties
 */

use Reactions\Snippet\ReactionsSchemaSnippet;

return (new ReactionsSchemaSnippet($modx))->process($scriptProperties);
