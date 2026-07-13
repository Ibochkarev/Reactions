# Сниппет ReactionsCount

Выводит текстовые счётчики реакций без кнопок. Подходит для карточек, списков и мета-строк.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | `class_key` объекта |
| `object` | ID текущего ресурса | ID объекта |
| `context` | ключ текущего контекста | Контекст MODX |
| `format` | `{TOTAL}` | Строка формата с плейсхолдерами |
| `type` | *(пусто)* | Фильтр по имени типа (`like`, `love`…) |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Плейсхолдеры формата

| Плейсхолдер | Описание |
| --- | --- |
| `{TOTAL}` | Сумма всех реакций |
| `{LIKES}` | Сумма `like` + `up` |
| `{DISLIKES}` | Сумма `dislike` + `down` |
| `{RATING}` | `LIKES − DISLIKES` |
| `{PCT_UP}` | Процент лайков от общего числа (0–100) |
| `{PCT_DOWN}` | Процент дизлайков от общего числа (0–100) |
| `{like}`, `{love}`, `{funny}`… | Счётчик конкретного типа по имени |

## Примеры

### Общее число реакций

MODX:

```
[[!ReactionsCount]]
```

Fenom:

```
{'!ReactionsCount' | snippet}
```

Результат: `15`

### Только лайки

MODX:

```
[[!ReactionsCount? &format=`{LIKES}`]]
```

Fenom:

```
{'!ReactionsCount' | snippet : ['format' => '{LIKES}']}
```

### Рейтинг с процентами

MODX:

```
[[!ReactionsCount? &format=`👍 {LIKES} ({PCT_UP}%) · 👎 {DISLIKES} ({PCT_DOWN}%)`]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'format' => '👍 {LIKES} ({PCT_UP}%) · 👎 {DISLIKES} ({PCT_DOWN}%)',
]}
```

### Один тип реакции

MODX:

```
[[!ReactionsCount? &type=`love` &format=`❤️ {love}`]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'type'   => 'love',
    'format' => '❤️ {love}',
]}
```

### Счётчик комментария Tickets

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

### На товаре miniShop3

MODX:

```
[[!ReactionsCount?
    &class=`msProduct`
    &object=`[[*id]]`
    &format=`{TOTAL} реакций`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'msProduct',
    'object' => $_modx->resource.id,
    'format' => '{TOTAL} реакций',
]}
```

### В плейсхолдер (карточка / pdoResources)

MODX:

```
[[!ReactionsCount?
    &object=`[[+id]]`
    &format=`{RATING}`
    &toPlaceholder=`rating`
]]
[[+rating]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'object' => $id,
    'format' => '{RATING}',
    'toPlaceholder' => 'rating',
]}
{$_modx->getPlaceholder('rating')}
```
