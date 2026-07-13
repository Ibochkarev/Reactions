<?php

namespace Reactions\Enum;

enum ReactionAction: string
{
    case Added = 'added';
    case Removed = 'removed';
    case Changed = 'changed';
}
