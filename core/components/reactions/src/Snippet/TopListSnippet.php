<?php

namespace Reactions\Snippet;

use MODX\Revolution\modX;
use Reactions\Enum\Period;

class TopListSnippet extends AbstractSnippet
{
    public function __construct(
        modX $modx,
        private readonly string $sortField,
        private readonly Period $defaultPeriod = Period::All,
    ) {
        parent::__construct($modx);
    }

    /** @param array<string, mixed> $scriptProperties */
    public function process(array $scriptProperties): string
    {
        $reactions = $this->reactions();
        $classKey = (string) ($scriptProperties['class'] ?? 'modResource');
        $limit = max(0, (int) ($scriptProperties['limit'] ?? 10));
        $tpl = (string) ($scriptProperties['tpl'] ?? 'tpl.Reactions.top');
        $period = $this->parsePeriod($scriptProperties, $this->defaultPeriod);
        $context = (string) ($scriptProperties['context'] ?? '');

        $rows = $reactions->getAggregateService()->listTop(
            $classKey,
            $this->sortField,
            $period,
            $limit,
            $context,
        );

        $output = '';
        $idx = 0;
        foreach ($rows as $aggregate) {
            $idx++;
            $output .= $this->modx->getChunk($tpl, [
                'idx' => $idx,
                'object_id' => (int) $aggregate->get('object_id'),
                'class_key' => (string) $aggregate->get('class_key'),
                'likes' => (int) $aggregate->get('likes'),
                'total' => (int) $aggregate->get('total'),
                'rating' => (int) $aggregate->get('rating'),
                'trending_score' => (float) $aggregate->get('trending_score'),
            ]);
        }

        return $this->finish($output, $scriptProperties);
    }
}
