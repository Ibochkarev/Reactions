# CLI

Запуск из корня сайта MODX:

```bash
php core/components/reactions/cli.php <команда> [подкоманда] [опции]
```

CLI инициализирует контекст `mgr`.

## Обзор команд

| Команда | Подкоманды | Назначение |
| --- | --- | --- |
| `recount` | — | Пересчёт агрегатов |
| `cleanup` | — | Очистка сирот и просроченных банов |
| `export` | — | Экспорт реакций в JSON |
| `type` | `create`, `list`, `remove` | Управление типами |
| `set` | `create`, `list`, `attach`, `remove` | Управление наборами |
| `ban` | `add`, `remove`, `list` | Баны IP и пользователей |
| `stats` | — | Статистика в консоль |

---

## recount

Пересчитывает `ReactionAggregate` из сырых записей.

```bash
# Все объекты с реакциями или агрегатами
php core/components/reactions/cli.php recount

# Один объект
php core/components/reactions/cli.php recount --class-key=modResource --object-id=5 --context=web
```

Опции:

| Опция | Описание |
| --- | --- |
| `--class-key` | `class_key` объекта |
| `--object-id` | ID объекта |
| `--context` | Контекст, по умолчанию `web` |

---

## cleanup

```bash
# Удалить реакции с несуществующими типами + просроченные баны
php core/components/reactions/cli.php cleanup --orphans

# Только просроченные баны
php core/components/reactions/cli.php cleanup
```

| Опция | Описание |
| --- | --- |
| `--orphans` | Удалить реакции, у которых `type_id` не существует |

---

## export

Экспорт реакций в JSON.

```bash
# В файл
php core/components/reactions/cli.php export --file=/tmp/reactions.json

# В stdout с фильтром
php core/components/reactions/cli.php export --class-key=modResource --object-id=5 --context=web
```

| Опция | Описание |
| --- | --- |
| `--class-key` | Фильтр по классу |
| `--object-id` | Фильтр по ID |
| `--context` | Фильтр по контексту |
| `--file` | Путь к файлу; без него вывод в stdout |

Формат файла:

```json
{
  "exported_at": 1710000000,
  "count": 2,
  "items": [
    {
      "id": 1,
      "class_key": "modResource",
      "object_id": 5,
      "context": "web",
      "type_id": 1,
      "type": { "id": 1, "name": "like", "emoji": "👍" },
      "user_id": null,
      "fingerprint": "f:abc...",
      "created_at": 1709900000,
      "updated_at": 1709900000
    }
  ]
}
```

---

## type

### type create

```bash
php core/components/reactions/cli.php type create --name=favorite --emoji=⭐ --ordering=90
```

| Опция | Описание |
| --- | --- |
| `--name` | Уникальное имя (обязательно) |
| `--emoji` | Эмодзи |
| `--ordering` | Порядок сортировки |
| `--icon` | Иконка (опционально) |
| `--inactive` | Создать неактивным |

### type list

```bash
php core/components/reactions/cli.php type list
```

### type remove

```bash
php core/components/reactions/cli.php type remove --name=favorite
php core/components/reactions/cli.php type remove --id=9
```

---

## set

### set create

```bash
php core/components/reactions/cli.php set create --key=social --title="Social" --types=like,love,funny
```

| Опция | Описание |
| --- | --- |
| `--key` | Уникальный ключ (обязательно) |
| `--title` | Заголовок |
| `--types` | Список типов через запятую |
| `--non-exclusive` | Разрешить несколько реакций |
| `--inactive` | Создать неактивным |

По умолчанию набор создаётся `exclusive`.

### set list

```bash
php core/components/reactions/cli.php set list
```

### set attach

Привязать типы к существующему набору.

```bash
php core/components/reactions/cli.php set attach --key=github --types=like,dislike,love --replace
```

| Опция | Описание |
| --- | --- |
| `--key` или `--id` | Набор |
| `--types` | Типы через запятую (обязательно) |
| `--replace` | Заменить все привязки (по умолчанию — замена) |

Без `--replace` новые типы добавляются к существующим.

### set remove

```bash
php core/components/reactions/cli.php set remove --key=social
php core/components/reactions/cli.php set remove --id=3
```

---

## ban

### ban add

```bash
php core/components/reactions/cli.php ban add --ip=203.0.113.10 --reason=spam --days=7
php core/components/reactions/cli.php ban add --user=42 --reason=abuse
php core/components/reactions/cli.php ban add --ip=1.2.3.4 --expires=1711000000
```

| Опция | Описание |
| --- | --- |
| `--ip` | IP-адрес (хешируется SHA-256) |
| `--user` | ID пользователя MODX |
| `--reason` | Причина |
| `--days` | Срок в днях от текущего момента |
| `--expires` | Unix timestamp или строка для `strtotime()` |

Нужен `--ip` или `--user`.

### ban remove

```bash
php core/components/reactions/cli.php ban remove --id=1
php core/components/reactions/cli.php ban remove --ip=203.0.113.10
php core/components/reactions/cli.php ban remove --user=42
```

### ban list

```bash
php core/components/reactions/cli.php ban list
```

---

## stats

```bash
php core/components/reactions/cli.php stats
php core/components/reactions/cli.php stats --limit=20
```

Выводит:

- Общие счётчики (реакции, агрегаты, типы, баны, сегодня).
- Топ по `likes`.
- Топ по `total`.
- Топ по `trending_score`.

---

## Примеры сценариев

### Добавить кастомный набор «Бар»

```bash
php core/components/reactions/cli.php type create --name=beer --emoji=🍺
php core/components/reactions/cli.php type create --name=fire --emoji=🔥
php core/components/reactions/cli.php set create --key=bar --title="Bar" --types=beer,fire --non-exclusive
```

### Пересчёт после импорта

```bash
php core/components/reactions/cli.php recount
php core/components/reactions/cli.php stats --limit=5
```

### Еженедельное обслуживание (cron)

```bash
0 3 * * 0 cd /var/www/site && php core/components/reactions/cli.php cleanup --orphans
```
