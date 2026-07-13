<?php

namespace MODX\Revolution;

/**
 * Minimal stub for unit tests outside a MODX installation.
 */
class modX
{
    public const LOG_LEVEL_WARN = 2;
    public const LOG_LEVEL_ERROR = 3;
    public const LOG_LEVEL_INFO = 1;

    public $user;
    public $resource;
    public $context;
    public $event;
    public $lexicon;
    public $request;
    public $services;

    public function getOption($key, $options = null, $default = null)
    {
        return $default;
    }

    public function lexicon($key, $params = [], $language = '')
    {
        return $key;
    }

    public function getCacheManager()
    {
        return null;
    }

    public function log($level, $message)
    {
    }

    public function getObject($class, $criteria = null)
    {
        return null;
    }

    public function newObject($class, $data = [])
    {
        return null;
    }

    public function getCount($class, $criteria = null)
    {
        return 0;
    }

    public function getCollection($class, $criteria = null)
    {
        return [];
    }

    public function getAuthenticatedUser($context = '')
    {
        return null;
    }

    public function getRequest()
    {
        return null;
    }

    public function hasPermission($permission)
    {
        return false;
    }

    public function beginTransaction()
    {
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }

    public function invokeEvent($eventName, array $params = [])
    {
        return true;
    }
}

class modResource
{
    public function get($k)
    {
        return null;
    }
}

class modUserMessage
{
    public function fromArray($a, $prefix = '', $setPrimaryKeys = false, $rawValues = false)
    {
        return true;
    }

    public function save()
    {
        return true;
    }
}
