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

## Примеры MODX

### Топ-5 ресурсов по рейтингу

```
[[!TopRated? &limit=`5`]]
```

### Лучшие статьи за месяц

```
[[!TopRated? &period=`month` &limit=`10` &tpl=`tpl.top.article`]]
```

### Топ товаров

```
[[!TopRated?
    &class=`msProduct`
    &period=`year`
    &limit=`8`
]]
```

## Примеры Fenom

### Блок «Лучшее за год»

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

```
{'!TopRated' | snippet : [
    'class'  => 'TicketComment',
    'period' => 'week',
    'limit'  => 3,
]}
```

### С кастомным чанком

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
