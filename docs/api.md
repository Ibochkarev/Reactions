# REST API

Базовый URL: `https://ваш-сайт/assets/components/reactions/api.php`

Маршрутизация через query-параметр `action` или `PATH_INFO`:

```
api.php?action=counts
api.php/counts
```

Все ответы — JSON с полем `success` (`true` / `false`).

## Формат ответов

Успех:

```json
{
  "success": true,
  "data": { ... }
}
```

Ошибка:

```json
{
  "success": false,
  "error": "Описание ошибки",
  "code": "error_code"
}
```

Коды ошибок: `validation_error`, `not_found`, `method_not_allowed`, `forbidden`, `rate_limit`, `auth_required`, `internal_error`.

---

## GET csrf

Получить CSRF-токен для POST/DELETE запросов.

```bash
curl -c cookies.txt "https://example.com/assets/components/reactions/api.php?action=csrf"
```

Ответ:

```json
{
  "success": true,
  "csrf": "a1b2c3d4..."
}
```

Токен привязан к PHP-сессии. Передавайте cookie в последующих запросах (`-b cookies.txt`).

---

## GET counts

Счётчики реакций и текущий выбор посетителя.

| Параметр | Обязательный | Описание |
| --- | --- | --- |
| `class_key` | да | `class_key` объекта |
| `object_id` | да | ID объекта |
| `context` | нет | Контекст, по умолчанию `web` |

```bash
curl -b cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=counts&class_key=modResource&object_id=1&context=web"
```

Ответ:

```json
{
  "success": true,
  "data": {
    "class_key": "modResource",
    "object_id": 1,
    "context": "web",
    "counts": {
      "like": 42,
      "dislike": 3,
      "love": 7
    },
    "total": 52,
    "user_reaction": ["like"]
  }
}
```

---

## POST react

Поставить реакцию.

Тело запроса (JSON):

| Поле | Обязательное | Описание |
| --- | --- | --- |
| `csrf` | да | CSRF-токен |
| `nonce` | да | Одноразовый nonce (32 hex-символа) |
| `class_key` | да | `class_key` объекта |
| `object_id` | да | ID объекта |
| `type` | да | Имя типа (`like`, `love`…) |
| `context` | нет | Контекст, по умолчанию `web` |
| `set` | нет | Ключ набора для проверки типа |

```bash
CSRF=$(curl -s -c cookies.txt -b cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=csrf" \
  | jq -r '.csrf')

NONCE=$(openssl rand -hex 16)

curl -s -b cookies.txt -c cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=react" \
  -H "Content-Type: application/json" \
  -H "Origin: https://example.com" \
  -d "{
    \"csrf\": \"$CSRF\",
    \"nonce\": \"$NONCE\",
    \"class_key\": \"modResource\",
    \"object_id\": 1,
    \"type\": \"like\",
    \"context\": \"web\",
    \"set\": \"github\"
  }"
```

Ответ:

```json
{
  "success": true,
  "data": {
    "action": "added",
    "counts": { "like": 43, "dislike": 3 },
    "total": 46,
    "user_reaction": ["like"],
    "type": "like"
  }
}
```

Значения `action`: `added`, `removed`, `changed`.

---

## DELETE react

Снять реакцию. Тело запроса — те же поля, что у POST.

```bash
curl -s -b cookies.txt \
  -X DELETE "https://example.com/assets/components/reactions/api.php?action=react" \
  -H "Content-Type: application/json" \
  -H "Origin: https://example.com" \
  -d "{
    \"csrf\": \"$CSRF\",
    \"nonce\": \"$NONCE\",
    \"class_key\": \"modResource\",
    \"object_id\": 1,
    \"type\": \"like\",
    \"context\": \"web\",
    \"set\": \"github\"
  }"
```

---

## GET top

Топ объектов по метрике.

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class_key` | `modResource` | `class_key` объектов |
| `sort` | `likes` | `likes`, `rating`, `total` |
| `period` | `all` | `day`, `week`, `month`, `year`, `all` |
| `context` | *(пусто)* | Фильтр по контексту |
| `limit` | `20` | Лимит (1–100) |
| `offset` | `0` | Смещение |

```bash
curl "https://example.com/assets/components/reactions/api.php?action=top&class_key=modResource&sort=likes&period=week&limit=10"
```

Ответ:

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "class_key": "modResource",
        "object_id": 42,
        "context": "web",
        "counts": { "like": 120, "dislike": 5 },
        "total": 125,
        "likes": 120,
        "dislikes": 5,
        "rating": 115,
        "trending_score": 2.079181
      }
    ],
    "limit": 10,
    "offset": 0,
    "total": 10
  }
}
```

---

## GET trending

Горячие материалы по `trending_score`.

| Параметр | По умолчанию | Описание |
| --- | --- | --- |
| `class_key` | `modResource` | `class_key` объектов |
| `context` | *(пусто)* | Фильтр по контексту |
| `limit` | `20` | Лимит (1–100) |
| `offset` | `0` | Смещение |

```bash
curl "https://example.com/assets/components/reactions/api.php?action=trending&class_key=modResource&limit=5"
```

---

## GET latest

Последние реакции (лента активности).

| Параметр | Описание |
| --- | --- |
| `class_key` | Фильтр по классу (опционально) |
| `context` | Фильтр по контексту (опционально) |
| `limit` | Лимит (1–100), по умолчанию 20 |
| `offset` | Смещение |

```bash
curl "https://example.com/assets/components/reactions/api.php?action=latest&class_key=modResource&limit=10"
```

Ответ:

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 501,
        "class_key": "modResource",
        "object_id": 42,
        "context": "web",
        "type": "like",
        "emoji": "👍",
        "user_id": 3,
        "created_at": 1710000000
      }
    ],
    "limit": 10,
    "offset": 0,
    "total": 10
  }
}
```

---

## Admin API {#admin}

Требует авторизации в менеджере MODX или политики `reactions_manage`.

Базовый путь: `action=admin/<resource>`

### GET admin/types

Список типов реакций.

```bash
curl -b mgr-cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=admin/types"
```

### POST admin/types

Создать или обновить тип.

```bash
curl -b mgr-cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=admin/types" \
  -H "Content-Type: application/json" \
  -d '{"name":"favorite","emoji":"⭐","ordering":90,"active":true}'
```

Обновление по `id`:

```bash
curl -b mgr-cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=admin/types" \
  -H "Content-Type: application/json" \
  -d '{"id":9,"emoji":"🌟","active":false}'
```

### DELETE admin/types

```bash
curl -b mgr-cookies.txt \
  -X DELETE "https://example.com/assets/components/reactions/api.php?action=admin/types" \
  -H "Content-Type: application/json" \
  -d '{"id":9}'
```

### GET admin/sets

Список наборов с привязанными типами.

```bash
curl -b mgr-cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=admin/sets"
```

### POST admin/sets

Создать или обновить набор. Поле `types` — массив имён или ID типов.

```bash
curl -b mgr-cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=admin/sets" \
  -H "Content-Type: application/json" \
  -d '{
    "key": "social",
    "title": "Social",
    "exclusive": false,
    "active": true,
    "types": ["like", "love", "funny"]
  }'
```

### DELETE admin/sets

```bash
curl -b mgr-cookies.txt \
  -X DELETE "https://example.com/assets/components/reactions/api.php?action=admin/sets" \
  -H "Content-Type: application/json" \
  -d '{"id":3}'
```

### GET admin/bans

Список банов.

```bash
curl -b mgr-cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=admin/bans"
```

### POST admin/bans

Добавить бан. IP хешируется на сервере.

```bash
curl -b mgr-cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=admin/bans" \
  -H "Content-Type: application/json" \
  -d '{"ip":"203.0.113.10","reason":"spam","expires_at":1711000000}'
```

Или по `user_id`:

```bash
curl -b mgr-cookies.txt \
  -X POST "https://example.com/assets/components/reactions/api.php?action=admin/bans" \
  -H "Content-Type: application/json" \
  -d '{"user_id":42,"reason":"abuse"}'
```

### DELETE admin/bans

```bash
curl -b mgr-cookies.txt \
  -X DELETE "https://example.com/assets/components/reactions/api.php?action=admin/bans" \
  -H "Content-Type: application/json" \
  -d '{"id":1}'
```

### GET admin/stats

Сводная статистика.

```bash
curl -b mgr-cookies.txt \
  "https://example.com/assets/components/reactions/api.php?action=admin/stats"
```

Ответ:

```json
{
  "success": true,
  "data": {
    "totals": {
      "reactions": 1500,
      "aggregates": 320,
      "types": 8,
      "bans": 2,
      "today": 45
    },
    "top_liked": [ ... ],
    "top_trending": [ ... ]
  }
}
```

---

## Безопасность

| Механизм | Описание |
| --- | --- |
| CSRF | Обязателен для POST/DELETE |
| Nonce | Одноразовый, TTL 300 секунд |
| Origin check | `Origin` / `Referer` должен совпадать с `site_url` |
| Rate limit | Настраивается через `reactions_rate_limit` |
| Bot detection | Блокировка краулеров через `reactions_block_bots` |

Запросы с `credentials: include` (как в JS-виджете) отправляют session cookie автоматически.
