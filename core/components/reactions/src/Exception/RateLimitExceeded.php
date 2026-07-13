<?php

namespace Reactions\Exception;

class RateLimitExceeded extends ReactionException
{
    public function __construct(string $message = 'Rate limit exceeded')
    {
        parent::__construct($message, 429, 'rate_limit');
    }
}
