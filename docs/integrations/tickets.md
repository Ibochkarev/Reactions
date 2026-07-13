# Интеграция с Tickets

Реакции на комментарии Tickets: `class_key` = `TicketComment`, `object` = ID комментария.

## Блок реакций под комментарием

MODX (чанк `tpl.Tickets.comment`):

```
<div class="comment-body">[[+text]]</div>
[[!Reactions?
    &class=`TicketComment`
    &object=`[[+id]]`
    &set=`updown`
]]
```

Fenom (шаблон списка комментариев):

```
{'!Reactions' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'set'    => 'updown',
]}
```

## Счётчик лайков

MODX:

```
[[!ReactionsCount?
    &class=`TicketComment`
    &object=`[[+id]]`
    &format=`👍 {LIKES}`
]]
```

Fenom:

```
{'!ReactionsCount' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'format' => '👍 {LIKES}',
]}
```

## Реакции на сам тикет (ресурс)

Tickets хранит тикеты как ресурсы MODX. Для реакций на страницу тикета:

MODX:

```
[[!Reactions? &set=`github`]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'class'  => 'modResource',
    'object' => $_modx->resource.id,
    'set'    => 'github',
]}
```

## Топ комментариев

MODX:

```
[[!TopLiked?
    &class=`TicketComment`
    &period=`week`
    &limit=`5`
    &tpl=`tpl.top.comment`
]]
```

Fenom:

```
{'!TopLiked' | snippet : [
    'class'  => 'TicketComment',
    'period' => 'week',
    'limit'  => 5,
    'tpl'    => 'tpl.top.comment',
]}
```

По рейтингу:

MODX:

```
[[!TopRated?
    &class=`TicketComment`
    &period=`month`
    &limit=`10`
]]
```

Fenom:

```
{'!TopRated' | snippet : [
    'class'  => 'TicketComment',
    'period' => 'month',
    'limit'  => 10,
]}
```

## Чанк для топа комментариев

`tpl.top.comment` (MODX-плейсхолдеры):

```html
<li class="top-comment" data-idx="[[+idx]]">
    <span class="top-comment__likes">[[+likes]] 👍</span>
    <span class="top-comment__rating">рейтинг: [[+rating]]</span>
    <span class="top-comment__id">#[[+object_id]]</span>
</li>
```

Fenom-эквивалент (`@INLINE` / файл):

```html
<li class="top-comment" data-idx="{$idx}">
    <span class="top-comment__likes">{$likes} 👍</span>
    <span class="top-comment__rating">рейтинг: {$rating}</span>
    <span class="top-comment__id">#{$object_id}</span>
</li>
```

Для ссылки на комментарий получите объект `TicketComment` по `object_id` в отдельном сниппете или через pdoTools.

## AJAX и контекст

Комментарии Tickets обычно рендерятся в контексте `web`. JS-виджет передаёт `context` из data-атрибута. Если комментарии в другом контексте, укажите `context` явно.

MODX:

```
[[!Reactions?
    &class=`TicketComment`
    &object=`[[+id]]`
    &set=`updown`
    &context=`web`
]]
```

Fenom:

```
{'!Reactions' | snippet : [
    'class'   => 'TicketComment',
    'object'  => $comment.id,
    'set'     => 'updown',
    'context' => 'web',
]}
```

## Модерация

Заблокируйте IP или пользователя через CLI:

```bash
php core/components/reactions/cli.php ban add --ip=203.0.113.10 --reason=spam --days=7
php core/components/reactions/cli.php ban add --user=42 --reason=abuse
```
