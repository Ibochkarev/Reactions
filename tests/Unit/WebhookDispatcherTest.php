<?php

use Reactions\Dto\ReactionRequest;
use Reactions\Dto\ReactionResult;
use Reactions\Enum\ReactionAction;
use Reactions\Service\WebhookDispatcher;

it('skips dispatch when webhooks disabled', function () {
    $modx = Mockery::mock(\MODX\Revolution\modX::class);
    $modx->shouldReceive('getOption')->with('reactions_webhook_url', null, '')->andReturn('https://example.test/hook');
    $modx->shouldReceive('log')->never();

    $reactions = Mockery::mock(\Reactions\Reactions::class)->makePartial();
    $reactions->modx = $modx;
    $reactions->shouldReceive('getOption')->with('webhooksEnabled', false)->andReturn(false);

    $dispatcher = new WebhookDispatcher($reactions);
    $dispatcher->dispatch(
        new ReactionResult(ReactionAction::Added, ['like' => 1], 1, ['like'], 'like'),
        new ReactionRequest('modResource', 1, 'like'),
    );

    expect(true)->toBeTrue();
});
