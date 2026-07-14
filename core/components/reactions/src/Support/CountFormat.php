<?php

namespace Reactions\Support;

final class CountFormat
{
    /**
     * @param array<string, int|string> $builtIns  e.g. {TOTAL} => '6'
     * @param array<string, int>        $named     type name => count
     */
    public static function apply(string $format, array $builtIns, array $named): string
    {
        $replacements = $builtIns;
        foreach ($named as $name => $count) {
            $replacements['{' . $name . '}'] = (string) $count;
        }

        // Type placeholders absent from aggregates (e.g. {love}) resolve to 0.
        if (preg_match_all('/\{([a-z][a-z0-9_]*)\}/', $format, $matches) > 0) {
            foreach ($matches[1] as $name) {
                $key = '{' . $name . '}';
                if (!array_key_exists($key, $replacements)) {
                    $replacements[$key] = '0';
                }
            }
        }

        // Longer keys first so {like} cannot clip inside {LIKES} if casing ever matches.
        uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
}
