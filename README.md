# Reactions

Реакции для MODX 3: от 👍/👎 до набора в стиле GitHub на ресурсе, товаре miniShop3 или комментарии Tickets.

Headless-компонент: UI менеджера нет. Настройки системы, REST API и CLI.

## Возможности

- Наборы: `updown` (👍/👎, exclusive), `github` (8 типов), `full` (24 типа, в т.ч. 🚀👀🔥👏🤔🥳⭐🍺✨💯🙏💪😎😍😕🙌).
- Layout виджета: `auto` (picker при >3 типах), `picker` (чипы + `+` / popover), `bar` (все кнопки в ряд).
- Свои типы и наборы через CLI или admin REST.
- В сниппетах/API: `class` / `class_key`; в БД поле `object_class` (STI xPDO).
- Повторный клик снимает реакцию. В exclusive другая заменяет предыдущую. Несколько типов сразу — если набор не exclusive и включён `reactions_allow_multiple`.
- Идентификация: только user, IP, IP+Cookie, Session.
- Топы за day/week/month/year/all; trending (формула Reddit).
- Rate limit, anti-bot, CSRF, Origin check, replay protection.
- События MODX, webhooks (Telegram, Discord, Slack), уведомления автору.

## Требования

- MODX Revolution 3.0+
- PHP 8.2–8.4
- MySQL / MariaDB (InnoDB)

## Установка

Транспортный пакет из репозитория дополнений или сборка:

```bash
composer install
php _build/build.php
```

`build.php` ставит npm-зависимости и собирает виджет (`frontend/` → `assets/…/js/web/`). Нужны Node.js и `npm`. Вручную: `cd frontend && npm install && npm run build`.

При локальной разработке из `Extras/Reactions` resolver ставит симлинки сайта на Extra. Подробности: [docs/index.md](docs/index.md#локальная-разработка-extras).

## Быстрый старт

Подключите `reactions.css` / `reactions.js`, затем вызовите сниппет. Для `github` / `full` при `layout=auto` виджет рисует picker; полосу кнопок даёт `layout=bar`.

MODX:

```
[[!Reactions? &set=`github`]]
[[!Reactions? &set=`github` &layout=`bar`]]
```

Fenom:

```
{'!Reactions' | snippet : ['set' => 'github']}
{'!Reactions' | snippet : ['set' => 'github', 'layout' => 'bar']}
```

Tickets:

```
{'!Reactions' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'set'    => 'updown',
]}
```

Топ по лайкам за неделю:

```
[[!TopLiked? &period=`week` &limit=`10` &tpl=`tpl.top.row`]]
```

Сортировка msProducts (pdoTools):

```
[[!msProducts?
    &leftJoin=`{"Aggregate":{"class":"Reactions\\Model\\ReactionAggregate","on":"Aggregate.object_id = msProduct.id AND Aggregate.object_class = 'msProduct'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
]]
```

## Документация

| Раздел | Ссылка |
| --- | --- |
| Установка, старт, настройки | [docs/index.md](docs/index.md) |
| REST API | [docs/api.md](docs/api.md) |
| JS-виджет (layout, data-*, BEM) | [docs/js.md](docs/js.md) |
| CLI | [docs/cli.md](docs/cli.md) |
| События MODX | [docs/events.md](docs/events.md) |
| Webhooks | [docs/webhooks.md](docs/webhooks.md) |

### Сниппеты

| Сниппет | Документация |
| --- | --- |
| `Reactions` | [docs/snippets/reactions.md](docs/snippets/reactions.md) |
| `ReactionsCount` | [docs/snippets/reactions-count.md](docs/snippets/reactions-count.md) |
| `TopLiked` | [docs/snippets/top-liked.md](docs/snippets/top-liked.md) |
| `TopRated` | [docs/snippets/top-rated.md](docs/snippets/top-rated.md) |
| `Trending` | [docs/snippets/trending.md](docs/snippets/trending.md) |
| `ReactionsSchema` | [docs/integrations/seo.md](docs/integrations/seo.md) |

### Интеграции

| Компонент | Документация |
| --- | --- |
| pdoTools | [docs/integrations/pdotools.md](docs/integrations/pdotools.md) |
| miniShop3 | [docs/integrations/minishop3.md](docs/integrations/minishop3.md) |
| Tickets | [docs/integrations/tickets.md](docs/integrations/tickets.md) (MODX 3: не проверено) |
| Collections | [docs/integrations/collections.md](docs/integrations/collections.md) |
| SEO / Schema.org | [docs/integrations/seo.md](docs/integrations/seo.md) |

## CLI

Из корня сайта MODX:

```bash
php core/components/reactions/cli.php recount
php core/components/reactions/cli.php type create --name=favorite --emoji=⭐
php core/components/reactions/cli.php ban add --ip=203.0.113.10
php core/components/reactions/cli.php stats
```

Справка: [docs/cli.md](docs/cli.md).

## Статус

Версия **1.0.0-pl**: модель, сервисы, REST API, сниппеты, TypeScript-виджет (picker/bar), CLI, документация, CI (PHP 8.2–8.4).

## Лицензия

MIT
