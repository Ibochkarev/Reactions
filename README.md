# Reactions

Универсальная система реакций для MODX 3: от пары «нравится / не нравится» до набора реакций в стиле GitHub — на любом объекте MODX.

Компонент headless: менеджерского интерфейса нет. Администрирование — через системные настройки, REST API и CLI.

## Возможности

- Два встроенных набора реакций:
  - `updown` — 👍 / 👎, взаимоисключающие;
  - `github` — 👍 👎 ❤️ 😂 😮 😢 😡 🎉, как в GitHub Reactions.
- Пользовательские реакции (⭐ 🔥 🍺 🚀) и собственные наборы — без программирования, через CLI или REST.
- Реакции на любой объект: ресурс, товар miniShop3, комментарий Tickets — модель хранит `class_key`, `object_id`, `context`.
- Повторное нажатие снимает реакцию, выбор другой — заменяет её. Настройка `allowMultiple` разрешает несколько реакций одновременно.
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

## Быстрый старт

Вывод блока реакций на странице ресурса.

Синтаксис MODX:

```
[[!Reactions? &set=`github`]]
```

Синтаксис Fenom:

```
{'!Reactions' | snippet : ['set' => 'github']}
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
    &leftJoin=`{"Aggregate":{"class":"ReactionAggregate","on":"Aggregate.object_id = msProduct.id AND Aggregate.class_key = 'msProduct'"}}`
    &sortby=`Aggregate.likes`
    &sortdir=`DESC`
]]
```

## Сниппеты

| Сниппет | Назначение |
| --- | --- |
| `Reactions` | Кнопки реакций с счётчиками |
| `ReactionsCount` | Счётчики без кнопок |
| `TopLiked` | Топ объектов по количеству реакций |
| `TopRated` | Топ по рейтингу (баланс up/down) |
| `Trending` | Горячие материалы по формуле Reddit |

Подробное описание параметров и примеры — в [docs/](docs/).

## API

Публичные endpoint'ы: счётчики, реакция пользователя, топы, лента последних реакций. Административные (с проверкой политик MODX): управление типами, наборами, банами, статистика. Спецификация — в [docs/api.md](docs/api.md).

## CLI

```bash
php core/vendor/bin/modx reactions:recount
php core/vendor/bin/modx reactions:type create --name=favorite --emoji=⭐
php core/vendor/bin/modx reactions:ban add --ip=203.0.113.10
php core/vendor/bin/modx reactions:stats
```

## Статус

Компонент в разработке, целевая версия 1.0.0-pl: модель данных и сервисы, AJAX/REST API, сниппеты, TypeScript-виджет, CLI, документация с примерами.

## Лицензия

MIT
