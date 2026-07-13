# Reactions — документация

Универсальная система реакций для MODX 3. Компонент headless: менеджерского интерфейса нет, управление через системные настройки, REST API и CLI.

## Требования

- MODX Revolution 3.0+
- PHP 8.2–8.4
- MySQL / MariaDB (InnoDB)

## Установка

### Из репозитория дополнений

Установите транспортный пакет `Reactions` через стандартный менеджер пакетов MODX.

### Сборка из исходников

```bash
composer install
php _build/build.php
```

После установки пакет создаёт таблицы, пресеты типов и наборов (`updown`, `github`), сниппеты, чанки и системные настройки.

## Быстрый старт

### 1. Подключите JS и CSS

В шаблоне или через `registerClientScript`:

```html
<link rel="stylesheet" href="[[++assets_url]]components/reactions/js/web/reactions.css">
<script src="[[++assets_url]]components/reactions/js/web/reactions.js" defer></script>
```

### 2. Выведите блок реакций

MODX:

```
[[!Reactions? &set=`github`]]
```

Fenom:

```
{'!Reactions' | snippet : ['set' => 'github', 'object' => $_modx->resource.id]}
```

### 3. Проверьте API

```bash
curl "https://example.com/assets/components/reactions/api.php?action=counts&class_key=modResource&object_id=1&context=web"
```

Виджет сам запрашивает CSRF-токен и обновляет счётчики через AJAX.

## Встроенные наборы реакций

| Ключ | Реакции | Режим |
| --- | --- | --- |
| `updown` | 👍 `like`, 👎 `dislike` | Взаимоисключающие (`exclusive`) |
| `github` | 👍 👎 ❤️ 😂 😮 😢 😡 🎉 | Несколько реакций одновременно |

Собственные типы и наборы создаются через [CLI](cli.md) или [административный API](api.md#admin).

## Сниппеты

| Сниппет | Назначение | Документация |
| --- | --- | --- |
| `Reactions` | Кнопки реакций с счётчиками | [snippets/reactions.md](snippets/reactions.md) |
| `ReactionsCount` | Счётчики без кнопок | [snippets/reactions-count.md](snippets/reactions-count.md) |
| `TopLiked` | Топ по количеству лайков | [snippets/top-liked.md](snippets/top-liked.md) |
| `TopRated` | Топ по рейтингу (likes − dislikes) | [snippets/top-rated.md](snippets/top-rated.md) |
| `Trending` | Горячие материалы по формуле Reddit | [snippets/trending.md](snippets/trending.md) |
| `ReactionsSchema` | JSON-LD `AggregateRating` для SEO | [integrations/seo.md](integrations/seo.md) |

## Интеграции

| Компонент | Документация |
| --- | --- |
| pdoTools | [integrations/pdotools.md](integrations/pdotools.md) |
| miniShop3 | [integrations/minishop3.md](integrations/minishop3.md) |
| Tickets | [integrations/tickets.md](integrations/tickets.md) |
| Collections | [integrations/collections.md](integrations/collections.md) |
| SEO / Schema.org | [integrations/seo.md](integrations/seo.md) |

## Системные настройки

Все настройки находятся в разделе **Система → Настройки системы → Reactions**.

| Ключ | По умолчанию | Описание |
| --- | --- | --- |
| `reactions_default_set` | `updown` | Набор реакций, если сниппет не передаёт `&set` |
| `reactions_identity_strategy` | `ip_cookie` | Стратегия идентификации посетителя: `auth_only`, `ip`, `ip_cookie`, `session` |
| `reactions_allow_multiple` | `Нет` | Разрешить несколько типов реакций на один объект (если набор не `exclusive`) |
| `reactions_rate_limit` | `10` | Максимум реакций за окно времени |
| `reactions_rate_limit_window` | `60` | Длина окна rate limit в секундах |
| `reactions_cache_handler` | `modx` | Обработчик кэша: `modx` или `redis` |
| `reactions_webhooks_enabled` | `Нет` | Отправлять HTTP-колбэки после изменения реакций |
| `reactions_webhook_url` | *(пусто)* | URL webhook (JSON, совместим с Telegram/Discord/Slack) |
| `reactions_notify_authors` | `Нет` | Уведомлять автора ресурса о новой реакции через `modUserMessage` |
| `reactions_block_bots` | `Да` | Отклонять реакции от известных краулеров и ботов |

### Стратегии идентификации

| Значение | Поведение |
| --- | --- |
| `auth_only` | Только авторизованные пользователи; гости получают ошибку |
| `ip` | Отпечаток по хешу IP |
| `ip_cookie` | Cookie `reactions_fid` (32 hex-символа), живёт 1 год |
| `session` | Отпечаток по ID PHP-сессии |

Авторизованный пользователь всегда идентифицируется по `user_id`, независимо от стратегии.

## Права доступа

| Политика | Описание |
| --- | --- |
| `reactions_view` | Просмотр данных реакций |
| `reactions_manage` | Управление типами, наборами и банами через API |
| `reactions_stats` | Просмотр статистики |

## Дополнительные разделы

- [REST API](api.md)
- [JavaScript-виджет](js.md)
- [CLI](cli.md)
- [События MODX](events.md)
- [Webhooks](webhooks.md)
