<?php

namespace Reactions\Api\Controller;

use MODX\Revolution\modX;
use Reactions\Api\RequestBody;
use Reactions\Enum\Period;
use Reactions\Reactions;

abstract class AbstractController
{
    public function __construct(protected readonly Reactions $reactions)
    {
    }

    abstract public function handle(string $method): void;

    protected function modx(): modX
    {
        return $this->reactions->modx;
    }

    protected function queryString(string $key, string $default = ''): string
    {
        $value = $_GET[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    protected function queryInt(string $key, int $default = 0): int
    {
        return (int) ($_GET[$key] ?? $default);
    }

    protected function pagination(): array
    {
        $limit = max(1, min(100, $this->queryInt('limit', 20)));
        $offset = max(0, $this->queryInt('offset', 0));

        return [$limit, $offset];
    }

    protected function parsePeriod(string $value): Period
    {
        return Period::tryFrom($value) ?? Period::All;
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonBody(): array
    {
        return RequestBody::all();
    }

    protected function bodyString(array $body, string $key, string $default = ''): string
    {
        $value = $body[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    protected function bodyInt(array $body, string $key, int $default = 0): int
    {
        return (int) ($body[$key] ?? $default);
    }

    protected function guardMutation(): void
    {
        $body = $this->jsonBody();
        $security = new \Reactions\Api\Security($this->modx());
        $security->validateMutation(
            $this->bodyString($body, 'csrf'),
            $this->bodyString($body, 'nonce'),
        );
    }
}
