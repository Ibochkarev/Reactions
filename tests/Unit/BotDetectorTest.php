<?php

use Reactions\Dto\VisitorIdentity;
use Reactions\Service\BotDetector;

function makeBotDetector(): BotDetector
{
    $reactions = Mockery::mock(\Reactions\Reactions::class);
    $reactions->modx = Mockery::mock(\MODX\Revolution\modX::class);

    return new BotDetector($reactions);
}

it('detects common crawler user agents', function (string $ua) {
    $detector = makeBotDetector();
    expect($detector->isBot($ua))->toBeTrue();
})->with([
    'Mozilla/5.0 (compatible; Googlebot/2.1)',
    'curl/8.0.0',
    'python-requests/2.28',
    'facebookexternalhit/1.1',
    'AhrefsBot/7.0',
]);

it('allows regular browser user agents', function () {
    $detector = makeBotDetector();
    expect($detector->isBot('Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/120.0.0.0'))->toBeFalse();
});

it('treats empty user agent as bot', function () {
    $detector = makeBotDetector();
    expect($detector->isBot(''))->toBeTrue();
});
