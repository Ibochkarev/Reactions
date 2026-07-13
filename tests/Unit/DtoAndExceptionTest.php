<?php

use Reactions\Dto\ReactionRequest;
use Reactions\Dto\ReactionResult;
use Reactions\Dto\VisitorIdentity;
use Reactions\Enum\ReactionAction;
use Reactions\Exception\AuthenticationRequired;
use Reactions\Exception\RateLimitExceeded;
use Reactions\Exception\ReactionNotAllowed;

it('builds reaction request dto', function () {
    $request = new ReactionRequest('modResource', 42, 'like', 'web', 'updown', false);

    expect($request->classKey)->toBe('modResource')
        ->and($request->objectId)->toBe(42)
        ->and($request->typeName)->toBe('like')
        ->and($request->setKey)->toBe('updown')
        ->and($request->allowMultiple)->toBeFalse();
});

it('serializes reaction result', function () {
    $result = new ReactionResult(
        ReactionAction::Added,
        ['like' => 3, 'dislike' => 1],
        4,
        ['like'],
        'like',
    );

    expect($result->toArray())->toMatchArray([
        'action' => 'added',
        'total' => 4,
        'user_reaction' => ['like'],
        'type' => 'like',
    ]);
});

it('builds visitor identity', function () {
    $identity = new VisitorIdentity('u:5', 5, 'abc', 'sess');

    expect($identity->fingerprint)->toBe('u:5')
        ->and($identity->userId)->toBe(5);
});

it('maps exception status codes', function () {
    expect((new RateLimitExceeded())->getStatusCode())->toBe(429)
        ->and((new ReactionNotAllowed())->getStatusCode())->toBe(403)
        ->and((new AuthenticationRequired())->getStatusCode())->toBe(401);
});
