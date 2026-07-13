<?php

namespace Reactions\Exception;

class ReactionNotAllowed extends ReactionException
{
    public function __construct(string $message = 'Reaction not allowed')
    {
        parent::__construct($message, 403, 'forbidden');
    }
}
