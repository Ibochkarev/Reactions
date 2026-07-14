<?php

namespace Reactions\Snippet;

class ReactionsSchemaSnippet extends AbstractSnippet
{
    /** @param array<string, mixed> $scriptProperties */
    public function process(array $scriptProperties): string
    {
        $reactions = $this->reactions();
        $classKey = (string) ($scriptProperties['class'] ?? 'modResource');
        $objectId = $this->resolveObjectId($this->modx, $scriptProperties);
        $context = $this->resolveContext($this->modx, $scriptProperties);

        $counts = $reactions->getAggregateService()->getCounts($classKey, $objectId, $context);
        $metrics = $this->metricsFromCounts($counts);

        $likes = $metrics['likes'];
        $dislikes = $metrics['dislikes'];
        $voted = $likes + $dislikes;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'AggregateRating',
        ];

        if ($voted > 0) {
            $schema['ratingValue'] = round(1 + 4 * ($likes / $voted), 1);
            $schema['ratingCount'] = $voted;
            $schema['bestRating'] = 5;
            $schema['worstRating'] = 1;
        } elseif ($likes > 0) {
            $schema['ratingCount'] = $likes;
        } else {
            return $this->finish('', $scriptProperties);
        }

        $json = json_encode(
            $schema,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return $this->finish(
            '<script type="application/ld+json">' . $json . '</script>',
            $scriptProperties,
        );
    }
}
