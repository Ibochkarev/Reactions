<?php

namespace Reactions\Snippet;

use MODX\Revolution\modResource;
use MODX\Revolution\modX;
use Reactions\Enum\Period;
use Reactions\Support\ObjectLookup;
use xPDO\Om\xPDOObject;

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
            $objectId = (int) $aggregate->get('object_id');
            $objectClass = (string) $aggregate->get('object_class');
            $meta = $this->resolveObjectMeta($objectClass, $objectId);

            $output .= $this->modx->getChunk($tpl, [
                'idx' => $idx,
                'object_id' => $objectId,
                'class_key' => $objectClass,
                'pagetitle' => $meta['pagetitle'],
                'uri' => $meta['uri'],
                'likes' => (int) $aggregate->get('likes'),
                'dislikes' => (int) $aggregate->get('dislikes'),
                'total' => (int) $aggregate->get('total'),
                'rating' => (int) $aggregate->get('rating'),
                'trending_score' => round((float) $aggregate->get('trending_score'), 2),
            ]);
        }

        return $this->finish($output, $scriptProperties);
    }

    /**
     * @return array{pagetitle: string, uri: string}
     */
    private function resolveObjectMeta(string $classKey, int $objectId): array
    {
        $fallback = $classKey . ' #' . $objectId;
        if ($objectId <= 0 || $classKey === '') {
            return ['pagetitle' => $fallback, 'uri' => ''];
        }

        $object = ObjectLookup::find($this->modx, $classKey, $objectId);
        if (!$object instanceof xPDOObject) {
            return ['pagetitle' => $fallback, 'uri' => ''];
        }

        $pagetitle = trim((string) (
            $object->get('pagetitle')
            ?: $object->get('name')
            ?: $object->get('title')
            ?: $fallback
        ));
        if ($pagetitle === '') {
            $pagetitle = $fallback;
        }

        $uri = '';
        if ($object instanceof modResource || is_a($object, modResource::class, false)) {
            $uri = (string) $this->modx->makeUrl($objectId, '', '', 'abs');
        } elseif (method_exists($object, 'get') && (int) $object->get('id') > 0) {
            // msProduct and STI resources usually resolve via makeUrl by id.
            $candidate = (string) $this->modx->makeUrl($objectId, '', '', 'abs');
            if ($candidate !== '' && !str_contains($candidate, '[[~')) {
                $uri = $candidate;
            }
        }

        return [
            'pagetitle' => $pagetitle,
            'uri' => $uri,
        ];
    }
}
