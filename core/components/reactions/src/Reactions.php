<?php

namespace Reactions;

use MODX\Revolution\modX;
use Reactions\Service\AggregateService;
use Reactions\Service\BotDetector;
use Reactions\Service\IdentityResolver;
use Reactions\Service\NotificationService;
use Reactions\Service\RateLimiter;
use Reactions\Service\ReactionService;
use Reactions\Service\TrendingCalculator;
use Reactions\Service\WebhookDispatcher;

class Reactions
{
    public modX $modx;

    /** @var array<string, mixed> */
    public array $config = [];

    private ?ReactionService $reactionService = null;
    private ?IdentityResolver $identityResolver = null;
    private ?RateLimiter $rateLimiter = null;
    private ?BotDetector $botDetector = null;
    private ?TrendingCalculator $trendingCalculator = null;
    private ?WebhookDispatcher $webhookDispatcher = null;
    private ?NotificationService $notificationService = null;
    private ?AggregateService $aggregateService = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $corePath = $modx->getOption(
            'reactions.core_path',
            $config,
            MODX_CORE_PATH . 'components/reactions/'
        );
        $assetsUrl = $modx->getOption(
            'reactions.assets_url',
            $config,
            MODX_ASSETS_URL . 'components/reactions/'
        );

        $this->config = array_merge([
            'corePath' => $corePath,
            'assetsUrl' => $assetsUrl,
            'apiUrl' => $assetsUrl . 'api.php',
            'jsUrl' => $assetsUrl . 'js/',
            'cssUrl' => $assetsUrl . 'css/',
            'defaultSet' => (string) $modx->getOption('reactions_default_set', null, 'updown'),
            'fullTypes' => (string) $modx->getOption('reactions_full_types', null, ''),
            'identityStrategy' => (string) $modx->getOption('reactions_identity_strategy', null, 'ip_cookie'),
            'allowMultiple' => (bool) $modx->getOption('reactions_allow_multiple', null, false),
            'rateLimit' => (int) $modx->getOption('reactions_rate_limit', null, 10),
            'rateLimitWindow' => (int) $modx->getOption('reactions_rate_limit_window', null, 60),
            'cacheHandler' => (string) $modx->getOption('reactions_cache_handler', null, 'modx'),
            'webhooksEnabled' => (bool) $modx->getOption('reactions_webhooks_enabled', null, false),
            'notifyAuthors' => (bool) $modx->getOption('reactions_notify_authors', null, false),
        ], $config);

        $modx->lexicon->load('reactions:default');
    }

    public function getReactionService(): ReactionService
    {
        return $this->reactionService ??= new ReactionService($this);
    }

    public function getIdentityResolver(): IdentityResolver
    {
        return $this->identityResolver ??= new IdentityResolver($this);
    }

    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter ??= new RateLimiter($this);
    }

    public function getBotDetector(): BotDetector
    {
        return $this->botDetector ??= new BotDetector($this);
    }

    public function getTrendingCalculator(): TrendingCalculator
    {
        return $this->trendingCalculator ??= new TrendingCalculator();
    }

    public function getWebhookDispatcher(): WebhookDispatcher
    {
        return $this->webhookDispatcher ??= new WebhookDispatcher($this);
    }

    public function getNotificationService(): NotificationService
    {
        return $this->notificationService ??= new NotificationService($this);
    }

    public function getAggregateService(): AggregateService
    {
        return $this->aggregateService ??= new AggregateService($this);
    }

    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}
