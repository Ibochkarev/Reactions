<?php

use Reactions\Service\TrendingCalculator;

it('scores positive hot posts above older low scores', function () {
    $calc = new TrendingCalculator();
    $now = 1700000000;
    $hot = $calc->score(100, $now);
    $cold = $calc->score(1, $now - 86400 * 30);

    expect($hot)->toBeGreaterThan($cold);
});

it('scores downvotes below upvotes at the same time', function () {
    $calc = new TrendingCalculator();
    $createdAt = 1700000000;
    $down = $calc->score(-10, $createdAt);
    $up = $calc->score(10, $createdAt);

    expect($down)->toBeLessThan($up);
});

it('treats zero score without sign contribution', function () {
    $calc = new TrendingCalculator();
    $createdAt = 1134028003;
    $score = $calc->score(0, $createdAt);

    expect($score)->toBe(0.0);
});

it('uses log10 of absolute score magnitude', function () {
    $calc = new TrendingCalculator();
    $createdAt = 1134028003;

    expect($calc->score(100, $createdAt))->toBe(2.0)
        ->and($calc->score(10, $createdAt))->toBe(1.0)
        ->and($calc->score(1, $createdAt))->toBe(0.0);
});
