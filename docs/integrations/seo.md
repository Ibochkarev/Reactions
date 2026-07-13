# SEO: ReactionsSchema

Сниппет `ReactionsSchema` выводит JSON-LD разметку `AggregateRating` для поисковых систем.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | `class_key` объекта |
| `object` | ID текущего ресурса | ID объекта |
| `context` | ключ текущего контекста | Контекст MODX |

## Логика расчёта

Сниппет берёт счётчики `like` и `dislike` (также учитывает `up`/`down`):

| Условие | Вывод |
| --- | --- |
| Есть и лайки, и дизлайки | `ratingValue` = 1…5 по формуле `1 + 4 × (likes / voted)`, `ratingCount` = likes + dislikes |
| Только лайки, без дизлайков | `ratingCount` = likes (без `ratingValue`) |
| Нет реакций | Пустая строка, тег не выводится |

Пример JSON-LD при 80 лайках и 20 дизлайках:

```json
{
  "@context": "https://schema.org",
  "@type": "AggregateRating",
  "ratingValue": 4.2,
  "ratingCount": 100,
  "bestRating": 5,
  "worstRating": 1
}
```

## Примеры

### На странице ресурса

В `<head>` или перед `</body>`:

MODX:

```
[[!ReactionsSchema]]
```

Fenom:

```
{raw ('!ReactionsSchema' | snippet)}
```

### На товаре miniShop3

MODX:

```
[[!ReactionsSchema?
    &class=`msProduct`
    &object=`[[*id]]`
]]
```

Fenom:

```
{raw ('!ReactionsSchema' | snippet : [
    'class'  => 'msProduct',
    'object' => $_modx->resource.id,
])}
```

### С явным контекстом

MODX:

```
[[!ReactionsSchema?
    &class=`modResource`
    &object=`[[*id]]`
    &context=`web`
]]
```

Fenom:

```
{raw ('!ReactionsSchema' | snippet : [
    'class'   => 'modResource',
    'object'  => $_modx->resource.id,
    'context' => 'web',
])}
```

`{raw …}` нужен, если Fenom экранирует HTML: иначе `<script type="application/ld+json">` превратится в текст.

## Связь с основным типом Schema.org

`AggregateRating` должен быть вложен в объект с `@type` (`Article`, `Product`, `BlogPosting` и т.д.). Сниппет выводит только блок рейтинга.

Вариант 1: оберните в свой сниппет или шаблон.

MODX:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "[[*pagetitle]]",
  "aggregateRating": {
    "ratingValue": 4.2,
    "ratingCount": 100,
    "bestRating": 5,
    "worstRating": 1
  }
}
</script>
```

Fenom:

```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{$_modx->resource.pagetitle | escape}",
  "aggregateRating": {
    "ratingValue": 4.2,
    "ratingCount": 100,
    "bestRating": 5,
    "worstRating": 1
  }
}
</script>
```

Вариант 2: выведите `ReactionsSchema` рядом с основной разметкой. Google объединяет блоки на одной странице, но вложенная структура надёжнее.

## Интеграция с SEO-плагинами

Если SEO-плагин уже генерирует JSON-LD, отключите дублирование `AggregateRating` в одном из источников. Проверьте результат в [Rich Results Test](https://search.google.com/test/rich-results).

## Когда разметка не появится

- На объекте нет ни одной реакции.
- Все реакции типов, отличных от like/dislike (например, только `love` в наборе `github`), без парных up/down — сниппет выведет `ratingCount` только при наличии лайков без дизлайков.

Для SEO на GitHub-наборе учитывайте, что `ratingValue` считается только из like/dislike.
