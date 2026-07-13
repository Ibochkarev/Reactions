<?php

namespace Reactions\Dto;

readonly class ReactionRequest
{
    public function __construct(
        public string $classKey,
        public int $objectId,
        public string $typeName,
        public string $context = 'web',
        public string $setKey = '',
        public bool $allowMultiple = false,
    ) {
    }
}
