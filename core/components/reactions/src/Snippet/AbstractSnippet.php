<?php

namespace Reactions\Snippet;

use MODX\Revolution\modX;
use Reactions\Reactions;

abstract class AbstractSnippet
{
    use SnippetSupport;

    public function __construct(
        protected readonly modX $modx,
    ) {
    }

    /** @param array<string, mixed> $scriptProperties */
    abstract public function process(array $scriptProperties): string;

    protected function reactions(): Reactions
    {
        /** @var Reactions $service */
        $service = $this->modx->services->get('Reactions');

        return $service;
    }

    /** @param array<string, mixed> $scriptProperties */
    protected function finish(string $output, array $scriptProperties): string
    {
        $placeholder = (string) ($scriptProperties['toPlaceholder'] ?? '');
        if ($placeholder !== '') {
            $this->modx->setPlaceholder($placeholder, $output);

            return '';
        }

        return $output;
    }
}
