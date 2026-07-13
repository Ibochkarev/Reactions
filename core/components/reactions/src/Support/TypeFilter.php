<?php

namespace Reactions\Support;

use Reactions\Model\ReactionType;

final class TypeFilter
{
    /**
     * @return list<string>
     */
    public static function parseTypeList(string $raw): array
    {
        $parts = preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [];
        }

        $names = [];
        foreach ($parts as $part) {
            $name = strtolower(trim((string) $part));
            if ($name === '' || in_array($name, $names, true)) {
                continue;
            }
            $names[] = $name;
        }

        return $names;
    }

    /**
     * @param list<ReactionType> $types
     * @param list<string>|null  $allow null = no filter; [] = show nothing
     *
     * @return list<ReactionType>
     */
    public static function filterTypes(array $types, ?array $allow): array
    {
        if ($allow === null) {
            return $types;
        }

        if ($allow === []) {
            return [];
        }

        $allowed = array_fill_keys($allow, true);
        $filtered = [];
        foreach ($types as $type) {
            $name = strtolower((string) $type->get('name'));
            if (isset($allowed[$name])) {
                $filtered[] = $type;
            }
        }

        return $filtered;
    }

    /**
     * Resolve allow-list for a set: snippet &types= wins; else full_types setting for set=full.
     *
     * @return list<string>|null null means show all types in the set
     */
    public static function resolveAllowList(string $setKey, string $typesProp, string $fullTypesSetting): ?array
    {
        $fromProp = self::parseTypeList($typesProp);
        if ($fromProp !== []) {
            return $fromProp;
        }

        if ($setKey === 'full') {
            $fromSetting = self::parseTypeList($fullTypesSetting);
            if ($fromSetting !== []) {
                return $fromSetting;
            }
        }

        return null;
    }

    /**
     * @param list<ReactionType> $types
     *
     * @return list<string>
     */
    public static function namesFromTypes(array $types): array
    {
        $names = [];
        foreach ($types as $type) {
            $names[] = (string) $type->get('name');
        }

        return $names;
    }
}
