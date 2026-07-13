<?php

namespace Reactions\Snippet;

use Reactions\Api\Security;

class ReactionsSnippet extends AbstractSnippet
{
    /** @param array<string, mixed> $scriptProperties */
    public function process(array $scriptProperties): string
    {
        $reactions = $this->reactions();
        $setKey = (string) ($scriptProperties['set'] ?? '');
        if ($setKey === '') {
            $setKey = (string) $reactions->getOption('defaultSet', 'updown');
        }

        $classKey = (string) ($scriptProperties['class'] ?? 'modResource');
        $objectId = $this->resolveObjectId($this->modx, $scriptProperties);
        $context = $this->resolveContext($this->modx, $scriptProperties);
        $tpl = (string) ($scriptProperties['tpl'] ?? 'tpl.Reactions');
        $tplOuter = (string) ($scriptProperties['tplOuter'] ?? 'tpl.Reactions.outer');

        $aggregate = $reactions->getAggregateService();
        $counts = $aggregate->getCounts($classKey, $objectId, $context);
        $metrics = $this->metricsFromCounts($counts);
        $identity = $reactions->getIdentityResolver()->resolve($reactions);
        $userReactions = $aggregate->getUserReactions($classKey, $objectId, $context, $identity);

        $csrf = '';
        try {
            $csrf = (new Security($this->modx))->createToken();
        } catch (\Throwable) {
            $csrf = '';
        }

        $buttons = '';
        foreach ($this->loadSetTypes($reactions, $setKey) as $type) {
            $name = (string) $type->get('name');
            $buttons .= $this->modx->getChunk($tpl, [
                'emoji' => (string) $type->get('emoji'),
                'name' => $name,
                'count' => (int) ($counts[$name] ?? 0),
                'active' => in_array($name, $userReactions, true) ? 1 : 0,
            ]);
        }

        $output = $this->modx->getChunk($tplOuter, [
            'output' => $buttons,
            'total' => $metrics['total'],
            'api_url' => (string) $reactions->getOption('apiUrl', ''),
            'csrf' => $csrf,
            'class_key' => $classKey,
            'object_id' => $objectId,
            'set' => $setKey,
            'context' => $context,
        ]);

        return $this->finish($output, $scriptProperties);
    }
}
