# Сниппет ReactionsCount

Текстовые счётчики без кнопок — для карточек, списков, мета-строк.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | Класс объекта (`class_key` в API) |
| `object` | ID текущего ресурса | ID объекта |
| `context` | ключ текущего контекста | Контекст MODX |
| `format` | `{TOTAL}` | Строка с плейсхолдерами |
| `type` | *(пусто)* | Узкий фильтр по имени типа (`like`, `love`…) |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Плейсхолдеры формата

| Плейсхолдер | Описание |
| --- | --- |
| `{TOTAL}` | Сумма всех реакций |
| `{LIKES}` | Сумма `like` + `up` |
| `{DISLIKES}` | Сумма `dislike` + `down` |
| `{RATING}` | `LIKES − DISLIKES` |
| `{PCT_UP}` | Доля лайков от общего, 0–100 |
| `{PCT_DOWN}` | Доля дизлайков от общего, 0–100 |
| `{like}`, `{love}`, `{funny}`… | Счётчик типа. Нет данных → `0`, не сырой `{love}` |

## Live-обновление

Сниппет оборачивает текст в:

```html
<span class="reactions-count"
  data-class-key="…"
  data-object-id="…"
  data-context="…"
  data-format="👍 {LIKES} · Σ {TOTAL}">…</span>
```

При клике по виджету Reactions на том же объекте (`class` + `object` + `context`) JS пересчитывает строку по `data-format` (событие `reactions:updated`). Нужен подключённый `reactions.js`.

## Примеры

### По умолчанию: только `{TOTAL}`

MODX:

```
[[!ReactionsCount]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'modResource',
    'object' => $_modx->resource.id,
]}
```

Пример вывода: `15`.

### TOTAL / LIKES / DISLIKES одной строкой

MODX:

```
[[!ReactionsCount?
    &format=`Всего {TOTAL}: 👍 {LIKES} / 👎 {DISLIKES}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'format' => 'Всего {TOTAL}: 👍 {LIKES} / 👎 {DISLIKES}',
]}
```

### Рейтинг и проценты

MODX:

```
[[!ReactionsCount?
    &format=`Рейтинг {RATING} · ↑{PCT_UP}% · ↓{PCT_DOWN}%`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'format' => 'Рейтинг {RATING} · ↑{PCT_UP}% · ↓{PCT_DOWN}%',
]}
```

### Один тип через `&type=`

MODX:

```
[[!ReactionsCount?
    &type=`like`
    &format=`Лайков: {like}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'type'   => 'like',
    'format' => 'Лайков: {like}',
]}
```

### Несколько именованных типов в `format`

Имена без отдельного `&type=`:

MODX:

```
[[!ReactionsCount?
    &format=`❤️ {love} · 🔥 {fire} · ⭐ {star} · 🚀 {rocket}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'format' => '❤️ {love} · 🔥 {fire} · ⭐ {star} · 🚀 {rocket}',
]}
```

Нулевые и отсутствующие типы печатаются как `0`.

### Только лайки

MODX:

```
[[!ReactionsCount? &format=`{LIKES}`]]
```

Fenom:

```
{'!ReactionsCount' | snippet : ['format' => '{LIKES}']}
```

### Товар miniShop3

MODX:

```
[[!ReactionsCount?
    &class=`msProduct`
    &object=`[[*id]]`
    &format=`Товар: 👍 {LIKES} · всего {TOTAL}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'format' => 'Товар: 👍 {LIKES} · всего {TOTAL}',
]}
```

### В плейсхолдер (pdoResources / карточка)

MODX:

```
[[!ReactionsCount?
    &object=`[[+id]]`
    &format=`Всего {TOTAL}: 👍 {LIKES} / 👎 {DISLIKES}`
    &toPlaceholder=`rx.count`
]]
[[+rx.count]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'object' => $id,
    'format' => 'Всего {TOTAL}: 👍 {LIKES} / 👎 {DISLIKES}',
    'toPlaceholder' => 'rx.count',
]}
{$_modx->getPlaceholder('rx.count')}
```

### Комментарий Tickets

На MODX 3 не проверено. Вызов:

MODX:

```
[[!ReactionsCount?
    &class=`TicketComment`
    &object=`[[+id]]`
    &format=`{LIKES}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'format' => '{LIKES}',
]}
```
