# JavaScript-виджет

Файлы:

| Файл | Назначение |
| --- | --- |
| `assets/components/reactions/js/web/reactions.js` | Логика виджета |
| `assets/components/reactions/js/web/reactions.css` | Стили |

## Подключение

```html
<link rel="stylesheet" href="[[++assets_url]]components/reactions/js/web/reactions.css">
<script src="[[++assets_url]]components/reactions/js/web/reactions.js" defer></script>
```

Сниппет `Reactions` выводит готовую разметку. Виджет инициализируется автоматически при загрузке DOM.

## Автоинициализация

Скрипт ищет элементы с классом `.reactions-widget` и монтирует виджет на каждый. Повторная инициализация одного элемента блокируется атрибутом `data-mounted="true"`.

## Data-атрибуты контейнера

| Атрибут | Обязательный | Описание |
| --- | --- | --- |
| `data-api` | да | URL API (`/assets/components/reactions/api.php`) |
| `data-class-key` | да | `class_key` объекта (`modResource`, `msProduct`…) |
| `data-object-id` | да | ID объекта (целое число > 0) |
| `data-set` | нет | Ключ набора, по умолчанию `updown` |
| `data-context` | нет | Контекст, по умолчанию `web` |
| `data-csrf` | нет | CSRF-токен; если пустой, виджет запросит сам |

Пример разметки:

```html
<div
    class="reactions-widget"
    data-api="/assets/components/reactions/api.php"
    data-class-key="modResource"
    data-object-id="42"
    data-set="github"
    data-context="web"
    data-csrf=""
></div>
```

Сниппет `Reactions` заполняет эти поля через чанк `tpl.Reactions.outer`. При кастомном чанке сохраните класс `reactions-widget` и корректные data-атрибуты.

## Ручная инициализация

Глобальный объект `window.Reactions`:

```javascript
// Все виджеты на странице
Reactions.init();

// Только внутри контейнера (после AJAX-подгрузки)
const container = document.getElementById('comments');
Reactions.init(container);
```

`init(root)` возвращает массив экземпляров `ReactionsWidget`.

## Жизненный цикл

1. Парсинг data-атрибутов.
2. Запрос CSRF (`GET ?action=csrf`), если токен не передан.
3. Загрузка счётчиков (`GET ?action=counts`).
4. Рендер кнопок по набору (`updown` или `github`).
5. По клику: optimistic UI → `POST ?action=react` или `DELETE ?action=react`.
6. При ошибке: откат состояния, обновление CSRF.

## Наборы в JS

Виджет знает эмодзи для встроенных наборов:

| Набор | Типы |
| --- | --- |
| `updown` | like 👍, dislike 👎 |
| `github` | like, dislike, love, funny, wow, sad, angry, hooray |

Для кастомных наборов серверная валидация работает через API, но JS отрисует кнопки только если набор совпадает с `REACTION_SETS` в бандле. Для полностью кастомных наборов используйте серверный рендер через сниппет `Reactions` без JS или расширьте фронтенд.

## Поведение updown

Набор `updown` обрабатывается как взаимоисключающий на клиенте: при выборе новой реакции предыдущая снимается оптимистично до ответа сервера.

## Доступность

- Контейнер: `role="group"`, `aria-label="Reactions"`.
- Кнопки: `aria-pressed`, `aria-label` с именем типа и счётчиком.
- Ошибки: `role="alert"`.
- Клавиатура: Enter и Space активируют кнопку.

## Стилизация

Классы BEM:

| Класс | Элемент |
| --- | --- |
| `.reactions-widget` | Контейнер |
| `.reactions-widget__buttons` | Группа кнопок |
| `.reactions-widget__button` | Кнопка реакции |
| `.reactions-widget__emoji` | Эмодзи |
| `.reactions-widget__count` | Счётчик |
| `.reactions-widget__error` | Сообщение об ошибке |

Активная кнопка в серверном чанке: `.reactions-btn--active`. Виджет после инициализации перерисовывает DOM и использует свои классы.

## AJAX после динамической подгрузки

Tickets и бесконечный скролл подгружают HTML без перезагрузки. После вставки новых комментариев вызовите:

```javascript
document.getElementById('new-comments').addEventListener('load', () => {
    Reactions.init(document.getElementById('new-comments'));
});
```

## Требования к cookie

Виджет отправляет запросы с `credentials: include`. Для стратегии `ip_cookie` браузер должен принимать cookie `reactions_fid`. CSRF привязан к PHP-сессии.

## Сборка из исходников

Исходники: `frontend/src/`. Сборка:

```bash
cd frontend
npm install
npm run build
```

Артефакт копируется в `assets/components/reactions/js/web/`.
