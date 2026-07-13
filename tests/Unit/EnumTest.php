<?php

use Reactions\Enum\IdentityStrategy;
use Reactions\Enum\Period;
use Reactions\Enum\ReactionAction;

it('parses identity strategy from setting with fallback', function () {
    expect(IdentityStrategy::fromSetting('auth_only'))->toBe(IdentityStrategy::AuthOnly)
        ->and(IdentityStrategy::fromSetting('ip'))->toBe(IdentityStrategy::Ip)
        ->and(IdentityStrategy::fromSetting('ip_cookie'))->toBe(IdentityStrategy::IpCookie)
        ->and(IdentityStrategy::fromSetting('session'))->toBe(IdentityStrategy::Session)
        ->and(IdentityStrategy::fromSetting('unknown'))->toBe(IdentityStrategy::IpCookie);
});

it('maps period to seconds', function () {
    expect(Period::Day->seconds())->toBe(86400)
        ->and(Period::Week->seconds())->toBe(604800)
        ->and(Period::Month->seconds())->toBe(2592000)
        ->and(Period::Year->seconds())->toBe(31536000)
        ->and(Period::All->seconds())->toBeNull();
});

it('exposes reaction action values', function () {
    expect(ReactionAction::Added->value)->toBe('added')
        ->and(ReactionAction::Removed->value)->toBe('removed')
        ->and(ReactionAction::Changed->value)->toBe('changed');
});
