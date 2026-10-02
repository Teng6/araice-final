<?php

namespace App\Enums;

enum SeverityEnum: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public static function fromFarmerCount(int $count): self
    {
        return match (true) {
            $count >= 8 => self::High,
            $count >= 5 => self::Medium,
            default => self::Low,
        };
    }
}
