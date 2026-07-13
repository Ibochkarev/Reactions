# Сниппет Trending

Выводит «горячие» материалы по формуле Reddit (алгоритм hot ranking). Сортировка по полю `trending_score`.

## Формула

```
sign = +1 если rating > 0, −1 если rating < 0, 0 если rating = 0
order = log10(max(|rating|, 1))
age = (время_последней_реакции − epoch) / 45000
trending_score = sign × order + age
```

Свежие материалы с высоким рейтингом поднимаются выше. Epoch зафиксирован: `1134028003` (27 декабря 2005).

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | `class_key` объектов в выборке |
| `period` | `all` | Период (для trending всегда используется `all`) |
| `limit` | `10` | Максимум элементов |
| `context` | *(пусто)* | Фильтр по контексту |
| `tpl` | `tpl.Reactions.top` | Чанк одной строки списка |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Плейсхолдеры чанка

| Плейсхолдер | Описание |
| --- | --- |
| `[[+idx]]` | Порядковый номер |
| `[[+object_id]]` | ID объекта |
| `[[+class_key]]` | `class_key` объекта |
| `[[+likes]]` | Количество лайков |
| `[[+total]]` | Сумма всех реакций |
| `[[+rating]]` | Рейтинг (likes − dislikes) |
| `[[+trending_score]]` | Оценка trending (для отладки и сортировки) |

## Примеры MODX

### Горячие материалы на главной

```
[[!Trending? &limit=`8`]]
```

### Трендовые товары

```
[[!Trending?
    &class=`msProduct`
    &limit=`6`
    &tpl=`tpl.trending.product`
]]
```

### Блок в плейсхолдер

```
[[!Trending? &limit=`5` &toPlaceholder=`trendingNow`]]
<div class="trending">[[+trendingNow]]</div>
```

## Примеры Fenom

### Лента «Сейчас обсуждают»

```
<h2>Сейчас обсуждают</h2>
<ul class="trending-list">
{'!Trending' | snippet : [
    'limit' => 10,
    'tpl'   => 'tpl.trending.row',
]}
</ul>
```

### Трендовые комментарии

```
{'!Trending' | snippet : [
    'class' => 'TicketComment',
    'limit' => 5,
]}
```

### Только контекст web

```
{'!Trending' | snippet : [
    'context' => 'web',
    'limit'   => 12,
]}
```

## Обновление trending_score

Поле `trending_score` пересчитывается при каждой реакции (в `AggregateService::recount`). Для массового пересчёта:

```bash
php core/components/reactions/cli.php recount
```
