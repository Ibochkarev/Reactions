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

## Примеры MODX

### Общее число реакций

```
[[!ReactionsCount]]
```

Результат: `15`

### Только лайки

```
[[!ReactionsCount? &format=`{LIKES}`]]
```

### Рейтинг с процентами

```
[[!ReactionsCount? &format=`👍 {LIKES} ({PCT_UP}%) · 👎 {DISLIKES} ({PCT_DOWN}%)`]]
```

### Один тип реакции

```
[[!ReactionsCount? &type=`love` &format=`❤️ {love}`]]
```

### Счётчик комментария Tickets

```
[[!ReactionsCount?
    &class=`TicketComment`
    &object=`[[+comment.id]]`
    &format=`{LIKES}`
]]
```

## Примеры Fenom

### В карточке ресурса

```
{'!ReactionsCount' | snippet : ['format' => '👍 {LIKES}']}
```

### На товаре miniShop3

```
{'!ReactionsCount' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'format' => '{TOTAL} реакций',
]}
```

### В списке комментариев

```
{'!ReactionsCount' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'format' => '{LIKES}',
]}
```

### В плейсхолдер для pdoResources

```
{'!ReactionsCount' | snippet : [
    'object' => $id,
    'format' => '{RATING}',
    'toPlaceholder' => 'rating',
]}
```
