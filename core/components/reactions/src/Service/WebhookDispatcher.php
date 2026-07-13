<?php

namespace Reactions\Service;

use Reactions\Dto\ReactionRequest;
use Reactions\Dto\ReactionResult;
use Reactions\Reactions;

class WebhookDispatcher
{
    public function __construct(
        private readonly Reactions $reactions,
    ) {
    }

    public function dispatch(ReactionResult $result, ReactionRequest $request): void
    {
        if (!$this->reactions->getOption('webhooksEnabled', false)) {
            return;
        }

        $url = trim((string) $this->reactions->modx->getOption('reactions_webhook_url', null, ''));
        if ($url === '') {
            return;
        }

        $payload = json_encode([
            'event' => 'reaction.' . $result->action->value,
            'class_key' => $request->classKey,
            'object_id' => $request->objectId,
            'context' => $request->context,
            'type' => $request->typeName,
            'result' => $result->toArray(),
            'timestamp' => time(),
        ], JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            $this->logError('Failed to encode webhook payload');
            return;
        }

        $this->send($url, $payload);
    }

    private function send(string $url, string $payload): void
    {
        if (function_exists('curl_init')) {
            $this->sendWithCurl($url, $payload);
            return;
        }

        $this->sendWithStream($url, $payload);
    }

    private function sendWithCurl(string $url, string $payload): void
    {
        $handle = curl_init($url);
        if ($handle === false) {
            $this->logError('curl_init failed');
            return;
        }

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | CURLPROTO_HTTP,
        ]);

        $response = @curl_exec($handle);
        $error = curl_error($handle);
        $code = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($response === false || $code >= 400) {
            $this->logError($error !== '' ? $error : 'Webhook HTTP ' . $code);
        }
    }

    private function sendWithStream(string $url, string $payload): void
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 3,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $this->logError('Webhook request failed');
        }
    }

    private function logError(string $message): void
    {
        $this->reactions->modx->log(
            \MODX\Revolution\modX::LOG_LEVEL_WARN,
            '[Reactions] Webhook: ' . $message
        );
    }
}
