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
| `[[+class_key]]` | Класс объекта (в БД: `object_class`) |
| `[[+pagetitle]]` | Заголовок ресурса / name товара |
| `[[+uri]]` | URL объекта (или пусто) |
| `[[+likes]]` | Количество лайков |
| `[[+dislikes]]` | Количество дизлайков |
| `[[+total]]` | Сумма всех реакций |
| `[[+rating]]` | Рейтинг (likes − dislikes) |
| `[[+trending_score]]` | Оценка trending (для отладки и сортировки) |

## Примеры

Параметр `period` для сортировки trending не режет окно: скор уже лежит в агрегатах (`period=all`). Укажите `period` только если так удобнее рядом с `TopLiked` / `TopRated` в одном шаблоне.

### Ресурсы на главной

MODX:

```
<ul class="reactions-top">
[[!Trending?
    &class=`modResource`
    &period=`all`
    &limit=`5`
]]
</ul>
```

Fenom:

```
<ul class="reactions-top">
{'!Trending' | snippet : [
    'class'  => 'modResource',
    'period' => 'all',
    'limit'  => 5,
]}
</ul>
```

### Короткий блок (limit=3)

MODX:

```
[[!Trending? &period=`all` &limit=`3`]]
```

Fenom:

```
{'!Trending' | snippet : ['period' => 'all', 'limit' => 3]}
```

### Товары miniShop3

MODX:

```
<ul class="reactions-top">
[[!Trending?
    &class=`msProduct`
    &limit=`6`
]]
</ul>
```

Fenom:

```
<ul class="reactions-top">
{'!Trending' | snippet : [
    'class' => 'msProduct',
    'limit' => 6,
]}
</ul>
```

### В плейсхолдер

MODX:

```
[[!Trending?
    &limit=`3`
    &toPlaceholder=`rx.trend`
]]
<ul class="reactions-top">[[+rx.trend]]</ul>
```

Fenom:

```
{'!Trending' | snippet : [
    'limit' => 3,
    'toPlaceholder' => 'rx.trend',
]}
<ul class="reactions-top">{$_modx->getPlaceholder('rx.trend')}</ul>
```

### Свой чанк

MODX:

```
<ul class="trending-list">
[[!Trending? &limit=`10` &tpl=`tpl.trending.row`]]
</ul>
```

Fenom:

```
<ul class="trending-list">
{'!Trending' | snippet : [
    'limit' => 10,
    'tpl'   => 'tpl.trending.row',
]}
</ul>
```

### Контекст `web`

```
[[!Trending? &context=`web` &limit=`12`]]
```

### Комментарии Tickets

На MODX 3 не проверено:

```
[[!Trending? &class=`TicketComment` &limit=`5`]]
```

## Обновление trending_score

Поле `trending_score` пересчитывается при каждой реакции (в `AggregateService::recount`). Для массового пересчёта:

```bash
php core/components/reactions/cli.php recount
```
