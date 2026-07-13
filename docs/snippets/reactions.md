# Сниппет Reactions

Выводит интерактивный блок реакций с счётчиками. Работает на любом объекте MODX: ресурс, товар miniShop3, комментарий Tickets.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `set` | из `reactions_default_set` | Ключ набора реакций (`updown`, `github`, `full` или свой) |
| `types` | *(пусто)* | Подмножество имён типов через запятую (пересечение с набором). Для `full` иначе берётся `reactions_full_types` |
| `class` | `modResource` | `class_key` объекта в xPDO |
| `object` | ID текущего ресурса | ID объекта |
| `context` | ключ текущего контекста | Контекст MODX (`web`, `mgr` и т.д.) |
| `tpl` | `tpl.Reactions` | Чанк одной кнопки реакции |
| `tplOuter` | `tpl.Reactions.outer` | Внешний чанк-обёртка |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Встроенные типы и наборы

Все пресетные типы (имя → эмодзи). Имена — то, что передаёте в `&types=` и `reactions_full_types`.

| `name` | Эмодзи | `updown` | `github` | `full` |
| --- | --- | --- | --- | --- |
| `like` | 👍 | ✓ | ✓ | ✓ |
| `dislike` | 👎 | ✓ | ✓ | ✓ |
| `love` | ❤️ | | ✓ | ✓ |
| `funny` | 😂 | | ✓ | ✓ |
| `wow` | 😮 | | ✓ | ✓ |
| `sad` | 😢 | | ✓ | ✓ |
| `angry` | 😡 | | ✓ | ✓ |
| `hooray` | 🎉 | | ✓ | ✓ |
| `rocket` | 🚀 | | | ✓ |
| `eyes` | 👀 | | | ✓ |
| `fire` | 🔥 | | | ✓ |
| `clap` | 👏 | | | ✓ |
| `thinking` | 🤔 | | | ✓ |
| `party` | 🥳 | | | ✓ |
| `star` | ⭐ | | | ✓ |
| `beer` | 🍺 | | | ✓ |
| `sparkles` | ✨ | | | ✓ |
| `hundred` | 💯 | | | ✓ |
| `pray` | 🙏 | | | ✓ |
| `muscle` | 💪 | | | ✓ |
| `cool` | 😎 | | | ✓ |
| `heart_eyes` | 😍 | | | ✓ |
| `confused` | 😕 | | | ✓ |
| `raised_hands` | 🙌 | | | ✓ |

Кратко по наборам:

- `updown` (2) — `like`, `dislike`; exclusive.
- `github` (8) — `like`, `dislike`, `love`, `funny`, `wow`, `sad`, `angry`, `hooray`.
- `full` (24) — все строки таблицы выше.

Пример значения для настройки или параметра:

```
like,love,fire,star,clap,rocket,heart_eyes
```

Собственные типы добавляются через [CLI](../cli.md) или [admin API](../api.md#admin) и подключаются к набору; в эту таблицу они не входят.

## Плейсхолдеры чанка кнопки (`tpl`)

| Плейсхолдер | Описание |
| --- | --- |
| `[[+emoji]]` | Эмодзи типа реакции |
| `[[+name]]` | Имя типа (`like`, `love`, `funny`…) |
| `[[+count]]` | Текущий счётчик |
| `[[+active]]` | `1` если пользователь поставил эту реакцию, иначе `0` |

## Плейсхолдеры обёртки (`tplOuter`)

| Плейсхолдер | Описание |
| --- | --- |
| `[[+output]]` | HTML всех кнопок |
| `[[+total]]` | Сумма всех реакций |
| `[[+api_url]]` | URL API; стандартный outer пишет в `data-api`. JS также умеет вывести API из пути `reactions.js` или `Reactions.config.api` |
| `[[+csrf]]` | CSRF-токен для AJAX |
| `[[+class_key]]` | `class_key` объекта |
| `[[+object_id]]` | ID объекта |
| `[[+set]]` | Ключ набора |
| `[[+context]]` | Контекст |
| `[[+types]]` | Имена показанных типов через запятую (`data-types` для JS) |

Для работы JS-виджета контейнер должен иметь класс `reactions-widget` и data-атрибуты объекта (`data-class-key`, `data-object-id`). `data-api` не обязателен. Подробнее — в [js.md](../js.md).

## Примеры

### Реакции на текущей странице (набор GitHub)

MODX:

```
[[!Reactions? &set=`github`]]
```

Fenom:

```
{'!Reactions' | snippet : ['set' => 'github']}
```

### Набор up/down на конкретном ресурсе

MODX:

```
[[!Reactions?
    &set=`updown`
    &object=`42`
]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'set' => 'updown',
    'object' => 42,
]}
```

### Набор `full`

MODX:

```
[[!Reactions? &set=`full`]]
```

Fenom:

```
{'!Reactions' | snippet : ['set' => 'full']}
```

Подмножество через системную настройку `reactions_full_types` (например `like,love,fire,star`) или параметр `types`:

MODX:

```
[[!Reactions? &set=`full` &types=`like,love,fire,star`]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'set'   => 'full',
    'types' => 'like,love,fire,star',
]}
```

Приоритет: `&types=` → иначе `reactions_full_types` (только для `full`) → все типы набора.
### Вывод в плейсхолдер

MODX:

```
[[!Reactions? &set=`github` &toPlaceholder=`pageReactions`]]
[[+pageReactions]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'set' => 'github',
    'toPlaceholder' => 'pageReactions',
]}
{$_modx->getPlaceholder('pageReactions')}
```

### Свой чанк

MODX:

```
[[!Reactions?
    &set=`github`
    &tpl=`myReactionBtn`
    &tplOuter=`myReactionsWrap`
]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'set' => 'github',
    'tpl' => 'myReactionBtn',
    'tplOuter' => 'myReactionsWrap',
]}
```

### Реакции на комментарий Tickets

MODX (в чанке строки комментария):

```
[[!Reactions?
    &class=`TicketComment`
    &object=`[[+id]]`
    &set=`updown`
]]
```

Fenom (внутри цикла по комментариям):

```
{'!Reactions' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'set'    => 'updown',
]}
```

### Реакции на товар miniShop3

MODX:

```
[[!Reactions?
    &class=`msProduct`
    &object=`[[*id]]`
    &set=`github`
    &context=`web`
]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'class'   => 'msProduct',
    'object'  => $_modx->resource.id,
    'set'     => 'github',
    'context' => 'web',
]}
```

## Поведение кнопок

- Повторное нажатие снимает реакцию.
- В наборе `updown` (и любом `exclusive`) выбор другой реакции заменяет предыдущую.
- Несколько типов одновременно: набор не `exclusive` **и** `reactions_allow_multiple=Да` (иначе сервер держит один тип на посетителя даже для `github` / `full`).
- Счётчики обновляются через AJAX без перезагрузки страницы.

## Подключение скриптов

Без `reactions.js` кнопки отображаются, но не реагируют на клики.

MODX:

```html
<link rel="stylesheet" href="[[++assets_url]]components/reactions/js/web/reactions.css">
<script src="[[++assets_url]]components/reactions/js/web/reactions.js" defer></script>
```

Fenom:

```html
<link rel="stylesheet" href="{'assets_url' | config}components/reactions/js/web/reactions.css">
<script src="{'assets_url' | config}components/reactions/js/web/reactions.js" defer></script>
```
