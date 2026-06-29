<?php

declare(strict_types=1);

namespace App\Enums;

enum MovieType: string
{
    case Movie = 'movie';
    case Series = 'series';
    case Episode = 'episode';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
