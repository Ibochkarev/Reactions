# Сниппет TopRated

Выводит список объектов с наивысшим рейтингом. Рейтинг = `likes − dislikes` (баланс up/down).

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | `class_key` объектов в выборке |
| `period` | `all` | Период: `day`, `week`, `month`, `year`, `all` |
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
| `[[+trending_score]]` | Оценка trending |

## Примеры

### Топ-5 ресурсов по рейтингу

MODX:

```
[[!TopRated? &limit=`5`]]
```

Fenom:

```
{'!TopRated' | snippet : ['limit' => 5]}
```

### Лучшие статьи за месяц

MODX:

```
[[!TopRated? &period=`month` &limit=`10` &tpl=`tpl.top.article`]]
```

Fenom:

```
{'!TopRated' | snippet : [
    'period' => 'month',
    'limit'  => 10,
    'tpl'    => 'tpl.top.article',
]}
```

### Топ товаров

MODX:

```
[[!TopRated?
    &class=`msProduct`
    &period=`year`
    &limit=`8`
]]
```

Fenom:

```
{'!TopRated' | snippet : [
    'class'  => 'msProduct',
    'period' => 'year',
    'limit'  => 8,
]}
```

### Блок «Лучшее за год»

MODX:

```
<section class="top-rated">
    <h2>Лучшее за год</h2>
    <ol>
    [[!TopRated? &period=`year` &limit=`10`]]
    </ol>
</section>
```

Fenom:

```
<section class="top-rated">
    <h2>Лучшее за год</h2>
    <ol>
    {'!TopRated' | snippet : [
        'period' => 'year',
        'limit'  => 10,
    ]}
    </ol>
</section>
```

### Рейтинг комментариев

MODX:

```
[[!TopRated?
    &class=`TicketComment`
    &period=`week`
    &limit=`3`
]]
```

Fenom:

```
{'!TopRated' | snippet : [
    'class'  => 'TicketComment',
    'period' => 'week',
    'limit'  => 3,
]}
```

### С кастомным чанком и плейсхолдером

MODX:

```
[[!TopRated?
    &limit=`5`
    &tpl=`myTopRatedRow`
    &toPlaceholder=`topRated`
]]
[[+topRated]]
```

Fenom:

```
{'!TopRated' | snippet : [
    'limit' => 5,
    'tpl'   => 'myTopRatedRow',
    'toPlaceholder' => 'topRated',
]}
{$_modx->getPlaceholder('topRated')}
```

## TopLiked vs TopRated

| Сниппет | Сортировка | Когда использовать |
| --- | --- | --- |
| `TopLiked` | По `likes` | «Самые популярные» — много положительных реакций |
| `TopRated` | По `rating` | «Лучшие» — высокий баланс лайков и дизлайков |

Статья с 100 лайками и 95 дизлайками попадёт в `TopLiked`, но не в `TopRated`.
