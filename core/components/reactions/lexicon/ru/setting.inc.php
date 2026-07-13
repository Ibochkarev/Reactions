<?php

$_lang['area_reactions_main'] = 'Основные';
$_lang['area_reactions_security'] = 'Безопасность';
$_lang['area_reactions_integrations'] = 'Интеграции';

$_lang['setting_reactions_default_set'] = 'Набор реакций по умолчанию';
$_lang['setting_reactions_default_set_desc'] = 'Ключ набора, если сниппет не передаёт &set (updown или github).';

$_lang['setting_reactions_identity_strategy'] = 'Стратегия идентификации';
$_lang['setting_reactions_identity_strategy_desc'] = 'auth_only, ip, ip_cookie или session.';

$_lang['setting_reactions_allow_multiple'] = 'Несколько реакций';
$_lang['setting_reactions_allow_multiple_desc'] = 'Разрешить посетителю ставить несколько типов реакций на один объект.';

$_lang['setting_reactions_rate_limit'] = 'Лимит реакций';
$_lang['setting_reactions_rate_limit_desc'] = 'Максимум реакций за окно времени.';

$_lang['setting_reactions_rate_limit_window'] = 'Окно лимита (секунды)';
$_lang['setting_reactions_rate_limit_window_desc'] = 'Длина окна rate limit в секундах.';

$_lang['setting_reactions_cache_handler'] = 'Обработчик кэша';
$_lang['setting_reactions_cache_handler_desc'] = 'modx (по умолчанию) или redis.';

$_lang['setting_reactions_webhooks_enabled'] = 'Включить webhooks';
$_lang['setting_reactions_webhooks_enabled_desc'] = 'Отправлять HTTP-колбэки после изменения реакций.';

$_lang['setting_reactions_webhook_url'] = 'URL webhook';
$_lang['setting_reactions_webhook_url_desc'] = 'Общий endpoint (JSON совместим с Telegram/Discord/Slack).';

$_lang['setting_reactions_notify_authors'] = 'Уведомлять авторов';
$_lang['setting_reactions_notify_authors_desc'] = 'Отправлять сообщение автору объекта после новой реакции.';

$_lang['setting_reactions_block_bots'] = 'Блокировать ботов';
$_lang['setting_reactions_block_bots_desc'] = 'Отклонять реакции от известных краулеров и ботов.';

$_lang['setting_reactions_allowed_classes'] = 'Разрешённые class_key';
$_lang['setting_reactions_allowed_classes_desc'] = 'Список class_key через запятую, на которые можно ставить реакции.';
