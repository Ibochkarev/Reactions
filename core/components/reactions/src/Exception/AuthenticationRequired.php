<?php

namespace Reactions\Exception;

class AuthenticationRequired extends ReactionException
{
    public function __construct(string $message = 'Authentication required')
    {
        parent::__construct($message, 401, 'auth_required');
    }
}
