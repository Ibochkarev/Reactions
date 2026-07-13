<?php

namespace Reactions\Service;

use Reactions\Dto\VisitorIdentity;
use Reactions\Model\ReactionBan;
use Reactions\Reactions;

class BotDetector
{
    /** @var list<string> */
    private const BOT_PATTERNS = [
        'bot',
        'crawl',
        'spider',
        'slurp',
        'facebookexternalhit',
        'mediapartners',
        'headless',
        'phantomjs',
        'selenium',
        'wget',
        'curl/',
        'python-requests',
        'scrapy',
        'bingpreview',
        'yandexbot',
        'googlebot',
        'baiduspider',
        'duckduckbot',
        'telegrambot',
        'discordbot',
        'whatsapp',
        'linkedinbot',
        'pinterestbot',
        'semrush',
        'ahrefsbot',
    ];

    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function isBot(?string $userAgent = null): bool
    {
        $ua = strtolower(trim($userAgent ?? (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
        if ($ua === '') {
            return true;
        }

        foreach (self::BOT_PATTERNS as $pattern) {
            if (str_contains($ua, $pattern)) {
                return true;
            }
        }

        return false;
    }

    public function isBlocked(VisitorIdentity $identity): bool
    {
        if ($this->isBannedByUser($identity->userId)) {
            return true;
        }

        return $this->isBannedByIp($identity->ipHash);
    }

    private function isBannedByUser(?int $userId): bool
    {
        if ($userId === null || $userId <= 0) {
            return false;
        }

        return $this->hasActiveBan(['user_id' => $userId]);
    }

    private function isBannedByIp(?string $ipHash): bool
    {
        if ($ipHash === null || $ipHash === '') {
            return false;
        }

        return $this->hasActiveBan(['ip_hash' => $ipHash]);
    }

    private function hasActiveBan(array $criteria): bool
    {
        $modx = $this->reactions->modx;
        $ban = $modx->getObject(ReactionBan::class, $criteria);

        if (!$ban) {
            return false;
        }

        $expiresAt = $ban->get('expires_at');
        if ($expiresAt !== null && (int) $expiresAt > 0 && (int) $expiresAt < time()) {
            return false;
        }

        return true;
    }
}
