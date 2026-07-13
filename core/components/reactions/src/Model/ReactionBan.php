<?php

namespace Reactions\Model;

/**
 * @property int $id
 * @property string|null $ip_hash
 * @property int|null $user_id
 * @property string|null $reason
 * @property int $created_at
 * @property int|null $expires_at
 */
class ReactionBan extends \xPDO\Om\xPDOSimpleObject
{
}
