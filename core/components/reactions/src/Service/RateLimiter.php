<?php

namespace Reactions\Service;

use Reactions\Dto\VisitorIdentity;
use Reactions\Exception\RateLimitExceeded;
use Reactions\Reactions;

class RateLimiter
{
    public function __construct(
        private readonly Reactions $reactions,
        private ?CacheService $cache = null,
    ) {
    }

    public function allow(VisitorIdentity $identity): void
    {
        $limit = (int) $this->reactions->getOption('rateLimit', 10);
        $window = (int) $this->reactions->getOption('rateLimitWindow', 60);

        if ($limit <= 0 || $window <= 0) {
            return;
        }

        $cache = $this->cache();
        $key = 'rl/' . $identity->fingerprint;
        $now = time();
        $bucket = $cache->get($key);

        if (!is_array($bucket) || ($now - (int) ($bucket['start'] ?? 0)) >= $window) {
            $cache->set($key, ['start' => $now, 'count' => 1], $window);
            return;
        }

        $count = (int) ($bucket['count'] ?? 0) + 1;
        if ($count > $limit) {
            throw new RateLimitExceeded(
                $this->reactions->modx->lexicon('reactions_err_rate_limit')
            );
        }

        $bucket['count'] = $count;
        $cache->set($key, $bucket, $window - ($now - (int) $bucket['start']));
    }

    private function cache(): CacheService
    {
        return $this->cache ??= new CacheService($this->reactions);
    }
}
