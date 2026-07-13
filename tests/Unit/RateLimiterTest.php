<?php

use Reactions\Dto\VisitorIdentity;
use Reactions\Exception\RateLimitExceeded;
use Reactions\Service\CacheService;
use Reactions\Service\RateLimiter;

it('allows requests under the limit then blocks', function () {
    $store = [];
    $cache = Mockery::mock(CacheService::class);
    $cache->shouldReceive('get')->andReturnUsing(function (string $key) use (&$store) {
        return $store[$key] ?? null;
    });
    $cache->shouldReceive('set')->andReturnUsing(function (string $key, mixed $value) use (&$store) {
        $store[$key] = $value;
        return true;
    });

    $modx = Mockery::mock(\MODX\Revolution\modX::class);
    $modx->shouldReceive('lexicon')->andReturn('rate limited');

    $reactions = Mockery::mock(\Reactions\Reactions::class)->makePartial();
    $reactions->modx = $modx;
    $reactions->shouldReceive('getOption')->with('rateLimit', 10)->andReturn(2);
    $reactions->shouldReceive('getOption')->with('rateLimitWindow', 60)->andReturn(60);

    $limiter = new RateLimiter($reactions, $cache);
    $identity = new VisitorIdentity('f:test');

    $limiter->allow($identity);
    $limiter->allow($identity);

    expect(fn () => $limiter->allow($identity))->toThrow(RateLimitExceeded::class);
});
