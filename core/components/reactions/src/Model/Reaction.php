<?php

namespace Reactions\Model;

/**
 * @property int $id
 * @property string $object_class
 * @property int $object_id
 * @property string $context
 * @property int $type_id
 * @property int|null $user_id
 * @property string $fingerprint
 * @property string|null $ip_hash
 * @property string|null $session_id
 * @property int $created_at
 * @property int $updated_at
 */
class Reaction extends \xPDO\Om\xPDOSimpleObject
{
}
