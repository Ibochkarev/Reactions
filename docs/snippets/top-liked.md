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
| `[[+class_key]]` | Класс объекта (в БД: `object_class`) |
| `[[+pagetitle]]` | Заголовок ресурса / name товара |
| `[[+uri]]` | URL объекта (или пусто) |
| `[[+likes]]` | Количество лайков |
| `[[+dislikes]]` | Количество дизлайков |
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

## Примеры

Обёртку `<ul class="reactions-top">` ставьте сами: чанк строки выводит `<li>…</li>`.

### Ресурсы: неделя / лимит

MODX:

```
<ul class="reactions-top">
[[!TopLiked?
    &class=`modResource`
    &period=`week`
    &limit=`5`
]]
</ul>
```

Fenom:

```
<ul class="reactions-top">
{'!TopLiked' | snippet : [
    'class'  => 'modResource',
    'period' => 'week',
    'limit'  => 5,
]}
</ul>
```

### Периоды `day` · `week` · `month` · `year` · `all`

Один и тот же вызов, меняете только `period`:

MODX:

```
[[!TopLiked? &period=`day` &limit=`5`]]
[[!TopLiked? &period=`month` &limit=`5`]]
[[!TopLiked? &period=`all` &limit=`10`]]
```

Fenom:

```
{'!TopLiked' | snippet : ['period' => 'day', 'limit' => 5]}
{'!TopLiked' | snippet : ['period' => 'month', 'limit' => 5]}
{'!TopLiked' | snippet : ['period' => 'all', 'limit' => 10]}
```

### Товары miniShop3

MODX:

```
<ul class="reactions-top">
[[!TopLiked?
    &class=`msProduct`
    &period=`month`
    &limit=`5`
]]
</ul>
```

Fenom:

```
<ul class="reactions-top">
{'!TopLiked' | snippet : [
    'class'  => 'msProduct',
    'period' => 'month',
    'limit'  => 5,
]}
</ul>
```

Заголовок и URI строки берёт `ObjectLookup` / ресурс STI — короткий `msProduct` подходит.

### Фильтр контекста `web`

MODX:

```
[[!TopLiked?
    &period=`all`
    &context=`web`
    &limit=`20`
]]
```

Fenom:

```
{'!TopLiked' | snippet : [
    'period'  => 'all',
    'context' => 'web',
    'limit'   => 20,
]}
```

### В плейсхолдер

MODX:

```
[[!TopLiked?
    &period=`all`
    &limit=`3`
    &toPlaceholder=`rx.top`
]]
<ul class="reactions-top">[[+rx.top]]</ul>
```

Fenom:

```
{'!TopLiked' | snippet : [
    'period' => 'all',
    'limit'  => 3,
    'toPlaceholder' => 'rx.top',
]}
<ul class="reactions-top">{$_modx->getPlaceholder('rx.top')}</ul>
```

### Свой чанк строки

MODX:

```
<ul class="top-liked">
[[!TopLiked?
    &period=`week`
    &limit=`10`
    &tpl=`tpl.top.row`
]]
</ul>
```

Fenom:

```
<ul class="top-liked">
{'!TopLiked' | snippet : [
    'period' => 'week',
    'limit'  => 10,
    'tpl'    => 'tpl.top.row',
]}
</ul>
```

### Комментарии Tickets

На MODX 3 не проверено:

```
[[!TopLiked? &class=`TicketComment` &period=`month` &limit=`5`]]
```
