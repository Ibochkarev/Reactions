<?php

namespace Reactions\Exception;

class ObjectNotFound extends ReactionException
{
    public function __construct(string $message = 'Object not found')
    {
        parent::__construct($message, 404, 'not_found');
    }
}
