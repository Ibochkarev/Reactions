<?php

namespace Reactions\Support;

/**
 * Normalize and sum reaction count maps (JSON/cache may hold junk strings).
 */
final class Counts
{
    /**
     * @return array<string, int>
     */
    public static function normalize(mixed $counts): array
    {
        if (is_string($counts)) {
            $decoded = json_decode($counts, true);
            $counts = is_array($decoded) ? $decoded : [];
        } elseif (!is_array($counts)) {
            $counts = [];
        }

        $normalized = [];
        foreach ($counts as $name => $value) {
            if (!is_string($name) || $name === '' || !is_numeric($value)) {
                continue;
            }
            $normalized[$name] = (int) $value;
        }

        return $normalized;
    }

    /**
     * @param array<array-key, mixed> $counts
     */
    public static function total(array $counts): int
    {
        $sum = 0;
        foreach ($counts as $value) {
            if (is_numeric($value)) {
                $sum += (int) $value;
            }
        }

        return $sum;
    }
}
