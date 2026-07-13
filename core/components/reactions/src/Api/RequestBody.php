<?php

namespace Reactions\Api;

class RequestBody
{
    /** @var array<string, mixed>|null */
    private static ?array $body = null;

    private static bool $parsed = false;

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$parsed) {
            return self::$body ?? [];
        }

        self::$parsed = true;
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            self::$body = $_POST;

            return self::$body;
        }

        $decoded = json_decode($raw, true);
        self::$body = is_array($decoded) ? $decoded : [];

        return self::$body;
    }
}
