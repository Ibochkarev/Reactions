<?php

namespace Reactions\Api;

use Reactions\Api\Controller\Admin\BansController;
use Reactions\Api\Controller\Admin\SetsController;
use Reactions\Api\Controller\Admin\StatsController;
use Reactions\Api\Controller\Admin\TypesController;
use Reactions\Api\Controller\CountsController;
use Reactions\Api\Controller\LatestController;
use Reactions\Api\Controller\ReactController;
use Reactions\Api\Controller\TopController;
use Reactions\Api\Controller\TrendingController;
use Reactions\Exception\AuthenticationRequired;
use Reactions\Exception\ReactionException;
use Reactions\Reactions;

class Router
{
    public function __construct(private readonly Reactions $reactions)
    {
    }

    public function handle(): void
    {
        try {
            $this->dispatch();
        } catch (ReactionException $e) {
            JsonResponse::error($e->getMessage(), $e->getErrorCode(), $e->getStatusCode());
        } catch (\Throwable $e) {
            $this->reactions->modx->log(
                \MODX\Revolution\modX::LOG_LEVEL_ERROR,
                sprintf(
                    '[Reactions API] %s in %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );
            JsonResponse::error('Internal server error', 'internal_error', 500);
        }
    }

    private function dispatch(): void
    {
        $method = $this->resolveMethod();
        $segments = $this->resolveRoute();

        if ($segments === []) {
            JsonResponse::error('Missing action', 'bad_request', 400);
        }

        $action = $segments[0];

        if ($action === 'csrf' && $method === 'GET') {
            $security = new Security($this->reactions->modx);
            JsonResponse::success(['csrf' => $security->createToken()]);
        }

        $controller = match ($action) {
            'counts' => new CountsController($this->reactions),
            'react' => new ReactController($this->reactions),
            'top' => new TopController($this->reactions),
            'trending' => new TrendingController($this->reactions),
            'latest' => new LatestController($this->reactions),
            'admin' => $this->resolveAdminController($segments),
            default => null,
        };

        if ($controller === null) {
            JsonResponse::error('Unknown action', 'not_found', 404);
        }

        $controller->handle($method);
    }

    /**
     * @param list<string> $segments
     */
    private function resolveAdminController(array $segments): TypesController|SetsController|BansController|StatsController
    {
        $this->requireAdmin();

        $resource = $segments[1] ?? '';

        return match ($resource) {
            'types' => new TypesController($this->reactions),
            'sets' => new SetsController($this->reactions),
            'bans' => new BansController($this->reactions),
            'stats' => new StatsController($this->reactions),
            default => throw new ReactionException('Unknown admin resource', 404, 'not_found'),
        };
    }

    private function requireAdmin(): void
    {
        if (!$this->reactions->modx->hasPermission('reactions_manage')) {
            throw new AuthenticationRequired();
        }
    }

    private function resolveMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method !== 'POST') {
            return $method;
        }

        if (isset($_POST['_method'])) {
            return strtoupper((string) $_POST['_method']);
        }

        $body = RequestBody::all();
        if (isset($body['_method'])) {
            return strtoupper((string) $body['_method']);
        }

        return $method;
    }

    /**
     * @return list<string>
     */
    private function resolveRoute(): array
    {
        $action = $_GET['action'] ?? '';
        if (!is_string($action)) {
            $action = '';
        }

        if ($action === '' && !empty($_SERVER['PATH_INFO'])) {
            $action = trim((string) $_SERVER['PATH_INFO'], '/');
        }

        if ($action === '') {
            return [];
        }

        return array_values(array_filter(explode('/', $action), static fn (string $part): bool => $part !== ''));
    }
}
