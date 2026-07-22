<?php

namespace App\Model;

enum Condition: string
{
    case AsNew = 'as new';
    case VeryGood = 'very good';
    case Acceptable = 'acceptable';

    public static function fromRaw(string $raw): self
    {
        return self::from(mb_strtolower(trim($raw)));
    }

    public function label(): string
    {
        return match ($this) {
            self::AsNew      => 'As new',
            self::VeryGood   => 'Very good',
            self::Acceptable => 'Acceptable',
        };
    }
}