<?php

namespace Reactions\Enum;

enum Period: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
    case Year = 'year';
    case All = 'all';

    public function seconds(): ?int
    {
        return match ($this) {
            self::Day => 86400,
            self::Week => 604800,
            self::Month => 2592000,
            self::Year => 31536000,
            self::All => null,
        };
    }
}
