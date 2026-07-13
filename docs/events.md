# События MODX

Reactions вызывает события при изменении реакций. Используйте их для логирования, кастомных уведомлений, интеграций с внешними сервисами.

## Список событий

| Событие | Когда срабатывает |
| --- | --- |
| `OnBeforeReaction` | Перед сохранением реакции; можно отменить |
| `OnAfterReaction` | После любого изменения (добавление, снятие, замена) |
| `OnReactionRemoved` | После снятия реакции |
| `OnReactionChanged` | После замены одного типа на другой |

## Параметры событий

### OnBeforeReaction

| Ключ | Тип | Описание |
| --- | --- | --- |
| `request` | `ReactionRequest` | Запрос: class_key, object_id, type, context, set |
| `identity` | `VisitorIdentity` | Отпечаток посетителя |
| `type` | `ReactionType` | Объект типа реакции |
| `set` | `ReactionSet` | Объект набора |

Отмена: установите `$modx->event->returnedValues['cancel'] = true`. Реакция не сохранится, API вернёт ошибку `forbidden`.

### OnAfterReaction, OnReactionRemoved, OnReactionChanged

| Ключ | Тип | Описание |
| --- | --- | --- |
| `request` | `ReactionRequest` | Запрос |
| `identity` | `VisitorIdentity` | Отпечаток посетителя |
| `type` | `ReactionType` | Тип реакции |
| `action` | `string` | `added`, `removed` или `changed` |

## Пример плагина: логирование

Создайте плагин `ReactionsLogger` с событиями `OnAfterReaction`:

```php
<?php
/** @var \MODX\Revolution\modX $modx */
/** @var \Reactions\Dto\ReactionRequest $request */
/** @var string $action */

$modx->log(
    \MODX\Revolution\modX::LOG_LEVEL_INFO,
    sprintf(
        '[Reactions] %s %s on %s#%d by fingerprint %s',
        $action,
        $request->typeName,
        $request->classKey,
        $request->objectId,
        $identity->fingerprint ?? 'unknown'
    )
);
```

## Пример плагина: запрет реакций на черновики

Плагин на `OnBeforeReaction`:

```php
<?php
/** @var \MODX\Revolution\modX $modx */
/** @var \Reactions\Dto\ReactionRequest $request */

if ($request->classKey !== 'modResource') {
    return;
}

$resource = $modx->getObject(\MODX\Revolution\modResource::class, $request->objectId);
if (!$resource) {
    return;
}

if (!(bool) $resource->get('published')) {
    $modx->event->returnedValues['cancel'] = true;
}
```

## Пример плагина: уведомление в Telegram

На `OnAfterReaction`, только для `added`:

```php
<?php
/** @var string $action */
/** @var \Reactions\Dto\ReactionRequest $request */

if ($action !== 'added') {
    return;
}

$token = $modx->getOption('telegram_bot_token');
$chatId = $modx->getOption('telegram_chat_id');
if (!$token || !$chatId) {
    return;
}

$text = urlencode(sprintf(
    'Новая реакция %s на %s#%d',
    $request->typeName,
    $request->classKey,
    $request->objectId
));

@file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text={$text}");
```

Для production используйте [webhooks](webhooks.md) или очередь, чтобы не блокировать ответ API.

## Пример плагина: инвалидация кэша Fenom

```php
<?php
/** @var \Reactions\Dto\ReactionRequest $request */

$modx->cacheManager->delete('resource/' . $request->objectId);
```

## Регистрация событий

При установке пакета события доступны в менеджере: **Элементы → Плагины → Системные события**. Привяжите плагин к нужным событиям и включите его.

## Порядок вызова

```
OnBeforeReaction
  → (транзакция: сохранение в БД)
OnAfterReaction
  → OnReactionRemoved  (если action = removed)
  → OnReactionChanged  (если action = changed)
  → пересчёт агрегата
  → webhook (если включён)
  → уведомление автору (если включено)
```

Плагины на `OnBeforeReaction` выполняются до проверки rate limit в текущей реализации только после `guardAccess`. Отмена через `cancel` бросает `ReactionNotAllowed`.

## Отладка

Включите уровень лога `INFO` и проверьте `core/cache/logs/error.log`. Для теста отмены отправьте реакцию на неопубликованный ресурс с плагином выше — API должен вернуть:

```json
{
  "success": false,
  "error": "...",
  "code": "forbidden"
}
```
