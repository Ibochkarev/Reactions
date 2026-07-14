<?php

namespace Reactions\Api;

class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data = [], int $statusCode = 200): never
    {
        self::emit($statusCode, array_merge(['success' => true], $data));
    }

    public static function error(string $message, string $code, int $statusCode = 400): never
    {
        self::emit($statusCode, [
            'success' => false,
            'error' => $message,
            'code' => $code,
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function emit(int $statusCode, array $payload): never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code($statusCode);
        try {
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            http_response_code(500);
            echo '{"success":false,"error":"JSON encode failed","code":"internal_error"}';
        }
        exit;
    }
}
