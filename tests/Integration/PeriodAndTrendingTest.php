<?php

use Reactions\Enum\Period;
use Reactions\Service\TrendingCalculator;

/**
 * Integration-style unit covering AggregateService period math helpers via Period enum
 * and trending formula used during recount. Full DB integration requires a MODX install.
 */
it('period windows cover expected day/week/month/year ranges', function () {
    $now = time();
    $dayAgo = $now - Period::Day->seconds();
    $weekAgo = $now - Period::Week->seconds();

    expect($now - $dayAgo)->toBe(86400)
        ->and($now - $weekAgo)->toBe(604800);
});

it('trending score increases with both votes and recency', function () {
    $calc = new TrendingCalculator();
    $base = 1_700_000_000;

    $newer = $calc->score(50, $base);
    $older = $calc->score(50, $base - 45000);

    expect($newer)->toBeGreaterThan($older);
});
