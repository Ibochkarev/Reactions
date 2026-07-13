<?php

namespace Reactions\Model;

/**
 * @property int $id
 * @property string $class_key
 * @property int $object_id
 * @property string $context
 * @property array $counts
 * @property int $total
 * @property int $likes
 * @property int $dislikes
 * @property int $rating
 * @property float $trending_score
 * @property int $updated_at
 */
class ReactionAggregate extends \xPDO\Om\xPDOSimpleObject
{
}
