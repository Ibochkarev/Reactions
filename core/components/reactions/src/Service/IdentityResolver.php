<?php

namespace Reactions\Service;

use MODX\Revolution\modX;
use Reactions\Api\Security;
use Reactions\Dto\VisitorIdentity;
use Reactions\Enum\IdentityStrategy;
use Reactions\Exception\AuthenticationRequired;
use Reactions\Reactions;

class IdentityResolver
{
    private const COOKIE_NAME = 'reactions_fid';

    /** Stable guest fingerprint for the current PHP request (setcookie does not populate $_COOKIE). */
    private ?string $requestFid = null;

    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function resolve(?Reactions $reactions = null): VisitorIdentity
    {
        $reactions ??= $this->reactions;
        $strategy = IdentityStrategy::fromSetting((string) $reactions->getOption('identityStrategy'));
        $modx = $reactions->modx;
        $ip = $this->resolveIp($modx);
        $ipHash = $ip !== '' ? hash('sha256', $ip) : null;
        $userId = $this->resolveUserId($modx);

        if ($strategy === IdentityStrategy::AuthOnly) {
            $this->requireAuthenticated($userId);
            return new VisitorIdentity('u:' . $userId, $userId, $ipHash);
        }

        if ($userId > 0) {
            return new VisitorIdentity('u:' . $userId, $userId, $ipHash);
        }

        return match ($strategy) {
            IdentityStrategy::Ip => new VisitorIdentity(
                'f:' . ($ipHash ?? hash('sha256', 'unknown')),
                null,
                $ipHash
            ),
            IdentityStrategy::Session => $this->resolveSession($ipHash),
            IdentityStrategy::IpCookie => $this->resolveIpCookie($ipHash),
        };
    }

    private function requireAuthenticated(?int $userId): void
    {
        if ($userId === null || $userId <= 0) {
            throw new AuthenticationRequired(
                $this->reactions->modx->lexicon('reactions_err_auth')
            );
        }
    }

    private function resolveUserId(modX $modx): ?int
    {
        $contextKey = $modx->context ? (string) $modx->context->get('key') : 'web';
        $user = $modx->getAuthenticatedUser($contextKey);

        return $user ? (int) $user->get('id') : null;
    }

    private function resolveIp(modX $modx): string
    {
        $request = $modx->request ?? null;
        if (is_object($request) && method_exists($request, 'getClientIp')) {
            $ip = $request->getClientIp();
            if (is_array($ip)) {
                return (string) ($ip['ip'] ?? '');
            }

            return (string) $ip;
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }

    private function resolveSession(?string $ipHash): VisitorIdentity
    {
        (new Security($this->reactions->modx))->ensureSession();
        $sessionId = session_id();
        if ($sessionId === '') {
            $sessionId = bin2hex(random_bytes(16));
        }

        return new VisitorIdentity('f:' . hash('sha256', $sessionId), null, $ipHash, $sessionId);
    }

    private function resolveIpCookie(?string $ipHash): VisitorIdentity
    {
        $fid = $this->readSignedFingerprint();
        if ($fid === null) {
            if ($this->requestFid === null) {
                $this->requestFid = bin2hex(random_bytes(16));
                $this->setFingerprintCookie($this->requestFid);
            }
            $fid = $this->requestFid;
        }

        return new VisitorIdentity('f:' . $fid, null, $ipHash);
    }

    private function readSignedFingerprint(): ?string
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!is_string($raw) || $raw === '' || !str_contains($raw, '.')) {
            return null;
        }

        [$fid, $signature] = explode('.', $raw, 2);
        if (!preg_match('/^[a-f0-9]{32}$/', $fid) || $signature === '') {
            return null;
        }

        $expected = $this->sign($fid);
        if (!hash_equals($expected, $signature)) {
            return null;
        }

        return $fid;
    }

    private function setFingerprintCookie(string $fid): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        $value = $fid . '.' . $this->sign($fid);
        $_COOKIE[self::COOKIE_NAME] = $value;
        setcookie(self::COOKIE_NAME, $value, [
            'expires' => time() + 31536000,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function sign(string $fid): string
    {
        $secret = (string) $this->reactions->modx->getOption('reactions_cookie_secret', null, '');
        if ($secret === '') {
            $secret = (string) $this->reactions->modx->getOption('site_id', null, 'reactions');
        }

        return hash_hmac('sha256', $fid, $secret);
    }
}
