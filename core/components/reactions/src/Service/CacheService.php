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
        $cached = $this->reactions->modx->getCacheManager()->get($this->prefixed($key));
        if ($cached === false || $cached === null) {
            return $default;
        }

        return $cached;
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        // xPDOCacheManager::set($key, &$var, $lifetime = 0, $options = [])
        $lifetime = max(0, $ttl);

        return (bool) $this->reactions->modx->getCacheManager()->set(
            $this->prefixed($key),
            $value,
            $lifetime
        );
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
