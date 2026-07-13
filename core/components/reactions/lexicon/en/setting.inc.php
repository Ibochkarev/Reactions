<?php

$_lang['area_reactions_main'] = 'Main';
$_lang['area_reactions_security'] = 'Security';
$_lang['area_reactions_integrations'] = 'Integrations';

$_lang['setting_reactions_default_set'] = 'Default reaction set';
$_lang['setting_reactions_default_set_desc'] = 'Key of the reaction set used when the snippet omits &set (updown or github).';

$_lang['setting_reactions_identity_strategy'] = 'Visitor identity strategy';
$_lang['setting_reactions_identity_strategy_desc'] = 'auth_only, ip, ip_cookie, or session.';

$_lang['setting_reactions_allow_multiple'] = 'Allow multiple reactions';
$_lang['setting_reactions_allow_multiple_desc'] = 'When enabled, a visitor may leave several reaction types on one object.';

$_lang['setting_reactions_rate_limit'] = 'Rate limit';
$_lang['setting_reactions_rate_limit_desc'] = 'Maximum reactions per time window.';

$_lang['setting_reactions_rate_limit_window'] = 'Rate limit window (seconds)';
$_lang['setting_reactions_rate_limit_window_desc'] = 'Length of the rate-limit window in seconds.';

$_lang['setting_reactions_cache_handler'] = 'Cache handler';
$_lang['setting_reactions_cache_handler_desc'] = 'modx (default) or redis.';

$_lang['setting_reactions_webhooks_enabled'] = 'Enable webhooks';
$_lang['setting_reactions_webhooks_enabled_desc'] = 'Send HTTP callbacks after reactions change.';

$_lang['setting_reactions_webhook_url'] = 'Webhook URL';
$_lang['setting_reactions_webhook_url_desc'] = 'Generic webhook endpoint (Telegram/Discord/Slack compatible JSON).';

$_lang['setting_reactions_notify_authors'] = 'Notify authors';
$_lang['setting_reactions_notify_authors_desc'] = 'Send a MODX message to the object author after a new reaction.';

$_lang['setting_reactions_block_bots'] = 'Block bots';
$_lang['setting_reactions_block_bots_desc'] = 'Reject reactions from known crawlers and bots.';

$_lang['setting_reactions_allowed_classes'] = 'Allowed class keys';
$_lang['setting_reactions_allowed_classes_desc'] = 'Comma-separated list of object class_key values that may receive reactions.';
