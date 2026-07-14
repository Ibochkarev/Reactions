# Интеграция с miniShop3

В сниппетах и API передавайте короткий класс `msProduct` и ID товара (`&object=` / `object_id`). Сервер находит товар через FQCN (`MiniShop3\Model\msProduct`) и STI `modResource`, короткое имя в xPDO не дергает — без шума в error.log.

В агрегатах поле `object_class` хранится как `msProduct` (то, что вы передали в `class` / `class_key`). Join в каталоге стройте по этой же строке.

## Блок реакций на карточке товара

`github` в режиме picker (чипы + `+`):

MODX:

```
[[!Reactions?
    &class=`msProduct`
    &object=`[[*id]]`
    &set=`github`
    &context=`web`
]]
```

Fenom (шаблон товара):

```
{'!Reactions' | snippet : [
    'class'   => 'msProduct',
    'object'  => $product.id,
    'set'     => 'github',
    'context' => 'web',
]}
```

Только 👍/👎:

```
[[!Reactions?
    &class=`msProduct`
    &object=`[[*id]]`
    &set=`updown`
]]
```

## Счётчик в каталоге

MODX:

```
[[!ReactionsCount?
    &class=`msProduct`
    &object=`[[+id]]`
    &format=`👍 {LIKES}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'format' => '👍 {LIKES}',
]}
```

## Сортировка каталога по популярности

Через `msProducts` и `leftJoin`.

MODX:

```
[[!msProducts?
    &parents=`10`
    &limit=`12`
    &leftJoin=`{"Aggregate":{"class":"Reactions\\Model\\ReactionAggregate","on":"Aggregate.object_id = msProduct.id AND Aggregate.object_class = 'msProduct'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
    &tpl=`tpl.msProduct.card`
]]
```

Fenom:

```
{'!msProducts' | snippet : [
    'parents' => 10,
    'limit' => 12,
    'leftJoin' => '{"Aggregate":{"class":"Reactions\\Model\\ReactionAggregate","on":"Aggregate.object_id = msProduct.id AND Aggregate.object_class = \'msProduct\'"}}',
    'sortby' => 'Aggregate.likes',
    'sortdir' => 'DESC',
    'tpl' => 'tpl.msProduct.card',
]}
```

## Топ товаров

MODX:

```
[[!TopLiked?
    &class=`msProduct`
    &period=`month`
    &limit=`6`
    &tpl=`tpl.top.product`
]]
```

Fenom:

```
{'!TopLiked' | snippet : [
    'class'  => 'msProduct',
    'period' => 'month',
    'limit'  => 6,
    'tpl'    => 'tpl.top.product',
]}
```

Трендовые товары:

MODX:

```
[[!Trending?
    &class=`msProduct`
    &limit=`8`
]]
```

Fenom:

```
{'!Trending' | snippet : [
    'class' => 'msProduct',
    'limit' => 8,
]}
```

## Счётчик в чанке msProducts

В `tpl.msProduct.card`:

MODX:

```
<div class="product-card">
    <h3>[[+pagetitle]]</h3>
    <p class="product-price">[[+price]] ₽</p>
    [[!ReactionsCount?
        &class=`msProduct`
        &object=`[[+id]]`
        &format=`❤️ {TOTAL}`
    ]]
</div>
```

Fenom:

```
<div class="product-card">
    <h3>{$pagetitle}</h3>
    <p class="product-price">{$price} ₽</p>
    {'!ReactionsCount' | snippet : [
        'class'  => 'msProduct',
        'object' => $id,
        'format' => '❤️ {TOTAL}',
    ]}
</div>
```

## Контекст

Товары обычно живут в контексте `web`. Если каталог в отдельном контексте, передайте `context`:

MODX:

```
[[!Reactions?
    &class=`msProduct`
    &object=`[[*id]]`
    &context=`catalog`
    &set=`updown`
]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'class'   => 'msProduct',
    'object'  => $product.id,
    'context' => 'catalog',
    'set'     => 'updown',
]}
```

## JSON-LD на карточке товара

Только при наличии like/dislike у товара. В Fenom — через `{raw …}`:

MODX:

```
[[!ReactionsSchema?
    &class=`msProduct`
    &object=`[[*id]]`
    &context=`web`
]]
```

Fenom:

```
{raw ('!ReactionsSchema' | snippet : [
    'class'   => 'msProduct',
    'object'  => $product.id,
    'context' => 'web',
])}
```

## Пересчёт агрегатов

После миграции или импорта:

```bash
php core/components/reactions/cli.php recount --class-key=msProduct
```
