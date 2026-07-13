<?php

namespace Reactions\Dto;

readonly class VisitorIdentity
{
    public function __construct(
        public string $fingerprint,
        public ?int $userId = null,
        public ?string $ipHash = null,
        public ?string $sessionId = null,
    ) {
    }
}
