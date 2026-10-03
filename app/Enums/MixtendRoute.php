<?php

namespace App\Enums;

/**
 * Mixtend のエンドポイント。値はベース URL からのパス。
 */
enum MixtendRoute: string
{
    case Schedule = 'schedule.json';

    public function method(): string
    {
        return match ($this) {
            self::Schedule => 'GET',
        };
    }
}
