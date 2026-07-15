<?php

namespace Reactions\Snippet;

use Reactions\Support\CountFormat;

class ReactionsCountSnippet extends AbstractSnippet
{
    /** @param array<string, mixed> $scriptProperties */
    public function process(array $scriptProperties): string
    {
        $reactions = $this->reactions();
        $classKey = (string) ($scriptProperties['class'] ?? 'modResource');
        $objectId = $this->resolveObjectId($this->modx, $scriptProperties);
        $context = $this->resolveContext($this->modx, $scriptProperties);
        $format = (string) ($scriptProperties['format'] ?? '{TOTAL}');
        $typeFilter = (string) ($scriptProperties['type'] ?? '');

        $counts = $reactions->getAggregateService()->getCounts($classKey, $objectId, $context);
        if ($typeFilter !== '') {
            $counts = array_intersect_key($counts, [$typeFilter => true]);
            if (!isset($counts[$typeFilter])) {
                $counts[$typeFilter] = 0;
            }
        }

        $metrics = $this->metricsFromCounts($counts);
        $total = $metrics['total'];
        $likes = $metrics['likes'];
        $dislikes = $metrics['dislikes'];
        $pctUp = $total > 0 ? (int) round($likes / $total * 100) : 0;
        $pctDown = $total > 0 ? (int) round($dislikes / $total * 100) : 0;

        $text = CountFormat::apply(
            $format,
            [
                '{TOTAL}' => (string) $total,
                '{LIKES}' => (string) $likes,
                '{DISLIKES}' => (string) $dislikes,
                '{RATING}' => (string) $metrics['rating'],
                '{PCT_UP}' => (string) $pctUp,
                '{PCT_DOWN}' => (string) $pctDown,
            ],
            $counts,
        );

        $attrs = [
            'class' => 'reactions-count',
            'data-class-key' => $classKey,
            'data-object-id' => (string) $objectId,
            'data-context' => $context,
            'data-format' => $format,
        ];
        if ($typeFilter !== '') {
            $attrs['data-type'] = $typeFilter;
        }

        $attrHtml = '';
        foreach ($attrs as $name => $value) {
            $attrHtml .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        $output = '<span' . $attrHtml . '>' . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';

        return $this->finish($output, $scriptProperties);
    }
}
