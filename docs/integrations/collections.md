# Интеграция с Collections

Реакции на ресурсы Collections работают как на обычных `modResource`. Collections не требует отдельного `class_key`.

## Блок реакций на странице коллекции

```
[[!Reactions? &set=`github`]]
```

Fenom:

```
{'!Reactions' | snippet : ['set' => 'github', 'object' => $_modx->resource.id]}
```

## Счётчик в списке дочерних ресурсов

В Fenom-шаблоне Collections (view `row`):

```
<article class="collection-item">
    <h2><a href="{$id | url}">{$pagetitle}</a></h2>
    {'!ReactionsCount' | snippet : [
        'object' => $id,
        'format' => '👍 {LIKES} · 💬 {TOTAL}',
    ]}
</article>
```

## Сортировка дочерних по популярности

Через pdoResources с `leftJoin` на `ReactionAggregate`:

```
[[!pdoResources?
    &parents=`[[*id]]`
    &depth=`1`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
    &tpl=`@INLINE <li><a href="[[+uri]]">[[+pagetitle]]</a> — [[!ReactionsCount? &object=`[[+id]]` &format=`{LIKES}`]]</li>`
]]
```

## Топ материалов коллекции

Ограничьте выборку родителем через pdoResources, а не через `TopLiked` напрямую. `TopLiked` сортирует глобально по `class_key`, без фильтра по родителю.

Вариант: pdoResources + sortby `Aggregate.likes` (см. выше).

Глобальный топ коллекции (все ресурсы):

```
[[!TopLiked?
    &class=`modResource`
    &period=`month`
    &limit=`10`
    &context=`web`
]]
```

## Шаблон коллекции с trending

На странице «Популярное в разделе»:

```
<h2>Горячие материалы</h2>
[[!pdoResources?
    &parents=`[[*id]]`
    &depth=`2`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = modResource.id AND Aggregate.class_key = 'modResource'"}}`
    &sortby=`Aggregate.trending_score`
    &sortdir=`DESC`
    &limit=`6`
    &tpl=`tpl.collection.trending`
]]
```

## Schema.org

Добавьте `ReactionsSchema` в шаблон ресурса коллекции:

```
[[!ReactionsSchema]]
```

Подробнее — в [seo.md](seo.md).
