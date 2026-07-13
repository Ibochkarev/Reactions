<?php

namespace Reactions\Enum;

enum IdentityStrategy: string
{
    case AuthOnly = 'auth_only';
    case Ip = 'ip';
    case IpCookie = 'ip_cookie';
    case Session = 'session';

    public static function fromSetting(string $value): self
    {
        return self::tryFrom($value) ?? self::IpCookie;
    }
}
