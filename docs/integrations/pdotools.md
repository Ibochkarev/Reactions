# Интеграция с pdoTools

Сортировка и вывод ресурсов, товаров и других объектов по реакциям через `leftJoin` на модель `ReactionAggregate`.

## Модель ReactionAggregate

Таблица `reactions_aggregates` хранит предрасчитанные метрики:

| Поле | Описание |
| --- | --- |
| `class_key` | Класс объекта (`modResource`, `msProduct`…) |
| `object_id` | ID объекта |
| `context` | Контекст MODX |
| `counts` | JSON со счётчиками по типам |
| `likes` | Сумма `like` + `up` |
| `dislikes` | Сумма `dislike` + `down` |
| `rating` | `likes − dislikes` |
| `total` | Сумма всех реакций |
| `trending_score` | Оценка trending |

## Сортировка ресурсов по лайкам

```
[[!pdoResources?
    &parents=`0`
    &depth=`10`
    &limit=`12`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource' AND Aggregate.context = 'web'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
    &tpl=`tpl.article.card`
]]
```

## Сортировка по рейтингу

```
[[!pdoResources?
    &parents=`5`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource'"}}`
    &sortby=`Aggregate.rating`
    &sortdir=`DESC`
    &limit=`10`
]]
```

## Сортировка по trending

```
[[!pdoResources?
    &parents=`0`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource'"}}`
    &sortby=`Aggregate.trending_score`
    &sortdir=`DESC`
    &limit=`8`
]]
```

## Вывод счётчиков в чанке pdoResources

В чанке `tpl.article.card` добавьте счётчик:

```
<h3>[[+pagetitle]]</h3>
<p>👍 [[!ReactionsCount? &object=`[[+id]]` &format=`{LIKES}`]]</p>
```

Или через плейсхолдер, если вызываете `ReactionsCount` с `&toPlaceholder`:

```
[[!ReactionsCount? &object=`[[+id]]` &format=`{LIKES}` &toPlaceholder=`likes_[[+id]]`]]
<span>[[+likes_[[+id]]]]</span>
```

## Fenom + pdoPage

В Fenom-шаблоне pdoPage:

```
{foreach $results as $row}
    <article>
        <h2><a href="{$row.id | url}">{$row.pagetitle}</a></h2>
        {'!ReactionsCount' | snippet : [
            'object' => $row.id,
            'format' => '👍 {LIKES}',
        ]}
    </article>
{/foreach}
```

## Фильтр: только объекты с реакциями

```
[[!pdoResources?
    &parents=`0`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource'"}}`
    &where=`{"Aggregate.likes:>":0}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
]]
```

## Примечания

- Убедитесь, что пакет `Reactions` зарегистрирован в xPDO: класс `ReactionAggregate` доступен по полному имени `Reactions\Model\ReactionAggregate`.
- Если агрегат для объекта отсутствует, `leftJoin` вернёт `NULL` — такие записи окажутся в конце при `DESC` или в начале при `ASC`.
- После массового импорта реакций запустите `php core/components/reactions/cli.php recount`.
