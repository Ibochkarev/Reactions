# Reactions

Универсальная система реакций для MODX 3: от пары «нравится / не нравится» до набора реакций в стиле GitHub — на любом объекте MODX.

Компонент headless: менеджерского интерфейса нет. Администрирование — через системные настройки, REST API и CLI.

## Возможности

- Три встроенных набора реакций:
  - `updown` — 👍 / 👎, взаимоисключающие;
  - `github` — 👍 👎 ❤️ 😂 😮 😢 😡 🎉;
  - `full` — расширенный набор (24 типа): `github` + 🚀👀🔥👏🤔🥳⭐🍺✨💯🙏💪😎😍😕🙌.
- Пользовательские реакции и собственные наборы — без программирования, через CLI или REST.
- Реакции на любой объект: ресурс, товар miniShop3, комментарий Tickets. В API/сниппетах — `class` / `class_key`; в БД поле называется `object_class` (STI xPDO).
- Повторное нажатие снимает реакцию. Другая реакция заменяет предыдущую в exclusive-режиме. Несколько типов сразу — только если набор не exclusive и включён `reactions_allow_multiple`.
- Стратегии идентификации посетителя: только авторизованные, IP, IP + Cookie, Session.
- Статистика и топы: за день, неделю, месяц, год, всё время; trending по формуле Reddit.
- Rate limit, защита от ботов, CSRF, Origin check, replay protection.
- События MODX, webhooks (Telegram, Discord, Slack), уведомления автору материала.

## Требования

- MODX Revolution 3.0+
- PHP 8.2–8.4
- MySQL / MariaDB (InnoDB)

## Установка

Через транспортный пакет из стандартного репозитория дополнений, либо сборка из исходников:

```bash
composer install
php _build/build.php
```

`build.php` сам ставит npm-зависимости и собирает виджет (`frontend/` → `assets/…/js/web/`). Нужны Node.js и `npm` в PATH. Вручную: `cd frontend && npm install && npm run build`.

## Быстрый старт

Вывод блока реакций на странице ресурса. Для `github` / `full` при `layout=auto` (по умолчанию) виджет рисует compact picker: чипы + кнопка `+`. Полосу из всех кнопок возвращает параметр `layout=bar`.

Синтаксис MODX:

```
[[!Reactions? &set=`github`]]
[[!Reactions? &set=`github` &layout=`bar`]]
```

Синтаксис Fenom:

```
{'!Reactions' | snippet : ['set' => 'github']}
{'!Reactions' | snippet : ['set' => 'github', 'layout' => 'bar']}
```

Реакции на комментарий Tickets:

```
{'!Reactions' | snippet : [
    'class'  => 'TicketComment',
    'object' => $comment.id,
    'set'    => 'updown',
]}
```

Топ ресурсов по лайкам за неделю:

```
[[!TopLiked? &period=`week` &limit=`10` &tpl=`tpl.top.row`]]
```

Сортировка товаров miniShop3 по реакциям через pdoTools:

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
| Установка, быстрый старт, настройки | [docs/index.md](docs/index.md) |
| REST API | [docs/api.md](docs/api.md) |
| JavaScript-виджет | [docs/js.md](docs/js.md) |
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

Подробности: [docs/cli.md](docs/cli.md).

## Статус

Компонент в разработке, целевая версия 1.0.0-pl: модель данных и сервисы, AJAX/REST API, сниппеты, TypeScript-виджет, CLI, документация с примерами.

## Лицензия

MIT
