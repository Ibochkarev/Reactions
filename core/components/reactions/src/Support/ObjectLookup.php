<?php

namespace Reactions\Support;

use MODX\Revolution\modResource;
use MODX\Revolution\modX;
use xPDO\Om\xPDOObject;

/**
 * Resolve MODX/xPDO objects by API class_key without xPDO error spam
 * for short MiniShop/Tickets aliases (msProduct, TicketComment, …).
 */
final class ObjectLookup
{
    /**
     * @return list<string>
     */
    public static function classCandidates(string $classKey): array
    {
        $classKey = trim($classKey);
        if ($classKey === '') {
            return [];
        }

        if (str_contains($classKey, '\\')) {
            return [$classKey];
        }

        $candidates = [
            'MiniShop3\\Model\\' . $classKey,
            'msProduct\\Model\\' . $classKey,
            'Tickets\\Model\\' . $classKey,
        ];

        // Native MODX short names (modResource, modUser…) are registered; custom short aliases are not.
        if (str_starts_with($classKey, 'mod')) {
            $candidates[] = $classKey;
        }

        return $candidates;
    }

    public static function find(modX $modx, string $classKey, int $objectId): ?xPDOObject
    {
        if ($objectId <= 0 || trim($classKey) === '') {
            return null;
        }

        foreach (self::classCandidates($classKey) as $class) {
            if (!self::isLoadable($class)) {
                continue;
            }
            $object = $modx->getObject($class, $objectId);
            if ($object instanceof xPDOObject) {
                return $object;
            }
        }

        return self::findResourceByAlias($modx, $classKey, $objectId);
    }

    public static function exists(modX $modx, string $classKey, int $objectId): bool
    {
        return self::find($modx, $classKey, $objectId) instanceof xPDOObject;
    }

    private static function isLoadable(string $class): bool
    {
        return class_exists($class, true);
    }

    private static function findResourceByAlias(modX $modx, string $classKey, int $objectId): ?xPDOObject
    {
        $resource = $modx->getObject(modResource::class, $objectId);
        if (!$resource instanceof xPDOObject) {
            return null;
        }

        if ($classKey === 'modResource' || $classKey === modResource::class) {
            return $resource;
        }

        $resourceClass = (string) $resource->get('class_key');
        if (
            $resourceClass === $classKey
            || str_ends_with($resourceClass, '\\' . $classKey)
        ) {
            return $resource;
        }

        return null;
    }
}
