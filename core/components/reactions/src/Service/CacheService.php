<?php

namespace Reactions\Service;

use Reactions\Reactions;

class CacheService
{
    private const PREFIX = 'reactions/';

    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cache = $this->reactions->modx->getCacheManager();

        return $cache->get($this->prefixed($key), $default);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        $cache = $this->reactions->modx->getCacheManager();
        $options = $ttl > 0 ? ['expires' => $ttl] : [];

        return (bool) $cache->set($this->prefixed($key), $value, $options);
    }

    public function delete(string $key): bool
    {
        return (bool) $this->reactions->modx->getCacheManager()->delete($this->prefixed($key));
    }

    private function prefixed(string $key): string
    {
        return self::PREFIX . ltrim($key, '/');
    }
}
