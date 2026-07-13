# Интеграция с miniShop3

Реакции на товары miniShop3: `class_key` = `msProduct`, `object` = ID товара.

## Блок реакций на карточке товара

MODX:

```
[[!Reactions?
    &class=`msProduct`
    &object=`[[*id]]`
    &set=`github`
]]
```

Fenom (шаблон товара):

```
{'!Reactions' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'set'    => 'github',
]}
```

## Счётчик в каталоге

```
{'!ReactionsCount' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'format' => '👍 {LIKES}',
]}
```

## Сортировка каталога по популярности

Через `msProducts` и `leftJoin`:

```
[[!msProducts?
    &parents=`10`
    &limit=`12`
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = msProduct.id AND Aggregate.class_key = 'msProduct'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
    &tpl=`tpl.msProduct.card`
]]
```

## Топ товаров

```
[[!TopLiked?
    &class=`msProduct`
    &period=`month`
    &limit=`6`
    &tpl=`tpl.top.product`
]]
```

Трендовые товары:

```
[[!Trending?
    &class=`msProduct`
    &limit=`8`
]]
```

## Счётчик в чанке msProducts

В `tpl.msProduct.card`:

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

## Контекст

Товары обычно живут в контексте `web`. Если каталог в отдельном контексте, передайте `&context`:

```
{'!Reactions' | snippet : [
    'class'   => 'msProduct',
    'object'  => $product.id,
    'context' => 'catalog',
    'set'     => 'updown',
]}
```

## Пересчёт агрегатов

После миграции или импорта:

```bash
php core/components/reactions/cli.php recount --class-key=msProduct
```
