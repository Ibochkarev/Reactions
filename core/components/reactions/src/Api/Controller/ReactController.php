<?php

namespace Reactions\Api\Controller;

use Reactions\Api\JsonResponse;
use Reactions\Api\Security;
use Reactions\Dto\ReactionRequest;
use Reactions\Exception\ReactionException;

class ReactController extends AbstractController
{
    public function handle(string $method): void
    {
        if (!in_array($method, ['POST', 'DELETE'], true)) {
            throw new ReactionException('Method not allowed', 405, 'method_not_allowed');
        }

        $body = $this->jsonBody();
        $security = new Security($this->modx());

        $security->validateOrigin();
        $security->validateCsrf($this->bodyString($body, 'csrf'));
        $security->validateNonce($this->bodyString($body, 'nonce'));

        $request = $this->buildRequest($body);
        $service = $this->reactions->getReactionService();

        $result = $method === 'DELETE'
            ? $service->unreact($request)
            : $service->react($request);

        JsonResponse::success(['data' => $result->toArray()]);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function buildRequest(array $body): ReactionRequest
    {
        $classKey = $this->bodyString($body, 'class_key');
        $objectId = $this->bodyInt($body, 'object_id');
        $typeName = $this->bodyString($body, 'type');

        if ($classKey === '' || $objectId <= 0 || $typeName === '') {
            throw new ReactionException(
                'class_key, object_id and type are required',
                400,
                'validation_error'
            );
        }

        return new ReactionRequest(
            classKey: $classKey,
            objectId: $objectId,
            typeName: $typeName,
            context: $this->bodyString($body, 'context', 'web'),
            setKey: $this->bodyString($body, 'set'),
            allowMultiple: (bool) $this->reactions->getOption('allowMultiple'),
        );
    }
}
