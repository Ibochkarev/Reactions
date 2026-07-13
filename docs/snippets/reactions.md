# Сниппет Reactions

Выводит интерактивный блок реакций с счётчиками. Работает на любом объекте MODX: ресурс, товар miniShop3, комментарий Tickets.

## Параметры

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `set` | из `reactions_default_set` | Ключ набора реакций (`updown`, `github` или свой) |
| `class` | `modResource` | `class_key` объекта в xPDO |
| `object` | ID текущего ресурса | ID объекта |
| `context` | ключ текущего контекста | Контекст MODX (`web`, `mgr` и т.д.) |
| `tpl` | `tpl.Reactions` | Чанк одной кнопки реакции |
| `tplOuter` | `tpl.Reactions.outer` | Внешний чанк-обёртка |
| `toPlaceholder` | *(пусто)* | Имя плейсхолдера вместо прямого вывода |

## Плейсхолдеры чанка кнопки (`tpl`)

| Плейсхолдер | Описание |
| --- | --- |
| `[[+emoji]]` | Эмодзи типа реакции |
| `[[+name]]` | Имя типа (`like`, `love`, `funny`…) |
| `[[+count]]` | Текущий счётчик |
| `[[+active]]` | `1` если пользователь поставил эту реакцию, иначе `0` |

## Плейсхолдеры обёртки (`tplOuter`)

| Плейсхолдер | Описание |
| --- | --- |
| `[[+output]]` | HTML всех кнопок |
| `[[+total]]` | Сумма всех реакций |
| `[[+api_url]]` | URL API (`assets/components/reactions/api.php`) |
| `[[+csrf]]` | CSRF-токен для AJAX |
| `[[+class_key]]` | `class_key` объекта |
| `[[+object_id]]` | ID объекта |
| `[[+set]]` | Ключ набора |
| `[[+context]]` | Контекст |

Для работы JS-виджета контейнер должен иметь класс `reactions-widget` и data-атрибуты. Подробнее — в [js.md](../js.md).

## Примеры MODX

### Реакции на текущей странице (набор GitHub)

```
[[!Reactions? &set=`github`]]
```

### Реакции up/down на конкретном ресурсе

```
[[!Reactions?
    &set=`updown`
    &object=`42`
]]
```

### Вывод в плейсхолдер

```
[[!Reactions? &set=`github` &toPlaceholder=`pageReactions`]]
[[+pageReactions]]
```

### Свой чанк

```
[[!Reactions?
    &set=`github`
    &tpl=`myReactionBtn`
    &tplOuter=`myReactionsWrap`
]]
```

## Примеры Fenom

### Текущий ресурс

```
{'!Reactions' | snippet : ['set' => 'github', 'object' => $_modx->resource.id]}
```

### Набор up/down

```
{'!Reactions' | snippet : ['set' => 'updown']}
```

### Реакции на комментарий Tickets

В шаблоне списка комментариев, внутри цикла `$comments`:

```
{'!Reactions' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'set'    => 'updown',
]}
```

### Реакции на товар miniShop3

```
{'!Reactions' | snippet : [
    'class'  => 'msProduct',
    'object' => $product.id,
    'set'    => 'github',
    'context' => 'web',
]}
```

### Вывод в переменную шаблона

```
{set $reactions = '!Reactions' | snippet : ['set' => 'github', 'toPlaceholder' => 'reactions']}
{$_modx->getPlaceholder('reactions')}
```

## Поведение кнопок

- Повторное нажатие снимает реакцию.
- В наборе `updown` (и любом `exclusive`) выбор другой реакции заменяет предыдущую.
- В наборе `github` посетитель может поставить несколько реакций, если `reactions_allow_multiple` включён или набор не `exclusive`.
- Счётчики обновляются через AJAX без перезагрузки страницы.

## Подключение скриптов

Без `reactions.js` кнопки отображаются, но не реагируют на клики:

```html
<link rel="stylesheet" href="[[++assets_url]]components/reactions/js/web/reactions.css">
<script src="[[++assets_url]]components/reactions/js/web/reactions.js" defer></script>
```
