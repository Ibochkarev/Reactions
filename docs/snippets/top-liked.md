# Сниппет TopLiked

Выводит список объектов с наибольшим числом лайков. Сортировка по полю `likes` в таблице агрегатов.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class` | `modResource` | `class_key` объектов в выборке |
| `period` | `all` | Период: `day`, `week`, `month`, `year`, `all` |
| `limit` | `10` | Максимум элементов |
| `context` | *(пусто)* | Фильтр по контексту; пустое значение — все контексты |
| `tpl` | `tpl.Reactions.top` | Чанк одной строки списка |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Плейсхолдеры чанка

| Плейсхолдер | Описание |
| --- | --- |
| `[[+idx]]` | Порядковый номер (1, 2, 3…) |
| `[[+object_id]]` | ID объекта |
| `[[+class_key]]` | `class_key` объекта |
| `[[+likes]]` | Количество лайков |
| `[[+total]]` | Сумма всех реакций |
| `[[+rating]]` | Рейтинг (likes − dislikes) |
| `[[+trending_score]]` | Оценка trending |

## Периоды

| Значение | Окно |
| --- | --- |
| `day` | Последние 24 часа |
| `week` | Последние 7 дней |
| `month` | Последние 30 дней |
| `year` | Последние 365 дней |
| `all` | Всё время (из таблицы агрегатов) |

Для периодов кроме `all` компонент пересчитывает топ по сырым записям реакций за окно.

## Примеры MODX

### Топ-10 ресурсов за неделю

```
[[!TopLiked? &period=`week` &limit=`10`]]
```

### Топ товаров miniShop3

```
[[!TopLiked?
    &class=`msProduct`
    &period=`month`
    &limit=`5`
    &tpl=`tpl.top.product`
]]
```

### Топ за день в плейсхолдер

```
[[!TopLiked? &period=`day` &limit=`5` &toPlaceholder=`topDay`]]
<ul>[[+topDay]]</ul>
```

## Примеры Fenom

### Сайдбар «Популярное за неделю»

```
<ul class="top-liked">
{'!TopLiked' | snippet : [
    'period' => 'week',
    'limit'  => 10,
    'tpl'    => 'tpl.top.row',
]}
</ul>
```

### Топ комментариев Tickets

```
{'!TopLiked' | snippet : [
    'class'  => 'TicketComment',
    'period' => 'month',
    'limit'  => 5,
]}
```

### Только контекст `web`

```
{'!TopLiked' | snippet : [
    'period'  => 'all',
    'context' => 'web',
    'limit'   => 20,
]}
```
