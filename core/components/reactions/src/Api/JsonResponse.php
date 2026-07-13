<?php

namespace Reactions\Api;

class JsonResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function success(array $data = [], int $statusCode = 200): never
    {
        http_response_code($statusCode);
        echo json_encode(
            array_merge(['success' => true], $data),
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        exit;
    }

    public static function error(string $message, string $code, int $statusCode = 400): never
    {
        http_response_code($statusCode);
        echo json_encode(
            [
                'success' => false,
                'error' => $message,
                'code' => $code,
            ],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
        exit;
    }
}
