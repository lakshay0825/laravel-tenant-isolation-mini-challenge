<?php

namespace App\Support;

final class TenantContext
{
    private static ?string $crpId = null;

    public static function set(?string $crpId): void
    {
        self::$crpId = $crpId;
    }

    public static function crpId(): ?string
    {
        return self::$crpId;
    }
}
