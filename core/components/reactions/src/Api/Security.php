<?php

namespace Reactions\Api;

use MODX\Revolution\modX;
use Reactions\Exception\ReactionNotAllowed;

class Security
{
    private const CSRF_SESSION_KEY = 'reactions_csrf';
    private const NONCE_SESSION_KEY = 'reactions_nonces';
    private const NONCE_TTL = 300;
    private const NONCE_MAX = 50;

    public function __construct(private readonly modX $modx)
    {
    }

    public function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // CLI / already-sent headers: keep an in-memory bag (no Set-Cookie).
        // In HTTP requests api.php must not send headers before initialize().
        if (PHP_SAPI === 'cli') {
            $_SESSION ??= [];

            return;
        }

        if (headers_sent($file, $line)) {
            $this->modx->log(
                modX::LOG_LEVEL_ERROR,
                '[Reactions] Cannot start session; headers already sent at ' . $file . ':' . $line
            );
            $_SESSION ??= [];

            return;
        }

        session_start();
    }

    public function createToken(): string
    {
        $this->ensureSession();
        $existing = $_SESSION[self::CSRF_SESSION_KEY] ?? '';
        // Multiple SSR widgets call createToken(); keep one token per session.
        if (is_string($existing) && preg_match('/^[a-f0-9]{64}$/', $existing) === 1) {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::CSRF_SESSION_KEY] = $token;

        return $token;
    }

    public function validateCsrf(?string $token): void
    {
        $this->ensureSession();

        $expected = $_SESSION[self::CSRF_SESSION_KEY] ?? '';
        if ($expected === '' || $token === null || $token === '') {
            throw new ReactionNotAllowed('Invalid or missing CSRF token');
        }

        if (!hash_equals($expected, $token)) {
            throw new ReactionNotAllowed('Invalid or missing CSRF token');
        }
    }

    public function validateOrigin(): void
    {
        $siteUrl = (string) $this->modx->getOption('site_url');
        $siteHost = parse_url($siteUrl, PHP_URL_HOST);
        if ($siteHost === null || $siteHost === '') {
            throw new ReactionNotAllowed('Origin not allowed');
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin !== '') {
            $originHost = parse_url($origin, PHP_URL_HOST);
            if ($originHost === null || strcasecmp($originHost, $siteHost) !== 0) {
                throw new ReactionNotAllowed('Origin not allowed');
            }

            return;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer === '') {
            throw new ReactionNotAllowed('Origin not allowed');
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        if ($refererHost === null || strcasecmp($refererHost, $siteHost) !== 0) {
            throw new ReactionNotAllowed('Origin not allowed');
        }
    }

    public function validateNonce(?string $nonce): void
    {
        $this->ensureSession();

        if ($nonce === null || $nonce === '' || strlen($nonce) > 64) {
            throw new ReactionNotAllowed('Missing nonce');
        }

        $now = time();
        $stored = $_SESSION[self::NONCE_SESSION_KEY] ?? [];
        if (!is_array($stored)) {
            $stored = [];
        }

        foreach ($stored as $key => $expiresAt) {
            if ($expiresAt < $now) {
                unset($stored[$key]);
            }
        }

        if (count($stored) >= self::NONCE_MAX) {
            $stored = array_slice($stored, -20, null, true);
        }

        if (isset($stored[$nonce])) {
            throw new ReactionNotAllowed('Nonce already used');
        }

        $stored[$nonce] = $now + self::NONCE_TTL;
        $_SESSION[self::NONCE_SESSION_KEY] = $stored;
    }

    public function validateMutation(?string $csrf, ?string $nonce): void
    {
        $this->validateOrigin();
        $this->validateCsrf($csrf);
        $this->validateNonce($nonce);
    }
}
