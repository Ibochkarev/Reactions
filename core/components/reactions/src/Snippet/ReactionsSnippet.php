<?php

namespace Reactions\Snippet;

use Reactions\Api\Security;
use Reactions\Model\ReactionSet;
use Reactions\Support\TypeFilter;

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

        $setTypes = $this->loadFilteredSetTypes($reactions, $setKey, $scriptProperties);
        $layoutRaw = strtolower((string) ($scriptProperties['layout'] ?? 'auto'));
        if (!in_array($layoutRaw, ['auto', 'bar', 'picker'], true)) {
            $layoutRaw = 'auto';
        }
        $usePicker = $layoutRaw === 'picker'
            || ($layoutRaw === 'auto' && count($setTypes) > 3);
        $layout = $usePicker ? 'picker' : 'bar';

        $buttons = '';
        foreach ($setTypes as $type) {
            $name = (string) $type->get('name');
            $count = (int) ($counts[$name] ?? 0);
            $active = in_array($name, $userReactions, true) ? 1 : 0;
            // Picker SSR: only chips that already have votes / user selection (JS adds the + menu).
            if ($usePicker && $count === 0 && $active === 0) {
                continue;
            }
            $buttons .= $this->modx->getChunk($tpl, [
                'emoji' => (string) $type->get('emoji'),
                'name' => $name,
                'count' => $count,
                'active' => $active,
            ]);
        }

        $set = $this->modx->getObject(ReactionSet::class, ['key' => $setKey, 'active' => true]);
        $setExclusive = $set ? (bool) $set->get('exclusive') : ($setKey === 'updown');
        $allowMultiple = (bool) $reactions->getOption('allowMultiple', false);

        $trigger = '';
        if ($usePicker) {
            $trigger = '<button type="button" class="reactions-widget__trigger" aria-haspopup="dialog" aria-expanded="false" aria-label="Add reaction" disabled>'
                . '<span class="reactions-widget__trigger-icon" aria-hidden="true">+</span>'
                . '</button>';
        }

        $output = $this->modx->getChunk($tplOuter, [
            'output' => $buttons,
            'trigger' => $trigger,
            'layout' => $layout,
            'total' => $metrics['total'],
            'api_url' => (string) $reactions->getOption('apiUrl', ''),
            'csrf' => $csrf,
            'class_key' => $classKey,
            'object_id' => $objectId,
            'set' => $setKey,
            'context' => $context,
            'types' => implode(',', TypeFilter::namesFromTypes($setTypes)),
            'exclusive' => $setExclusive ? '1' : '0',
            'allow_multiple' => $allowMultiple ? '1' : '0',
        ]);

        return $this->finish($output, $scriptProperties);
    }
}
