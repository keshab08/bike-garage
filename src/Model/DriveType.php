<?php

namespace App\Model;

enum DriveType: string
{
    case Pedelec = 'pedelec';
    case SPedelec = 's-pedelec';
    case NonElectric = 'non-electric';

    public static function fromRaw(string $raw): self
    {
        $value = mb_strtolower(trim($raw));

        if ($value === 'e-bike') {
            return self::Pedelec;
        }

        return self::from($value);
    }

    public function label(): string
    {
        return match ($this) {
            self::Pedelec     => 'Pedelec',
            self::SPedelec    => 'S-Pedelec',
            self::NonElectric => 'Non-electric',
        };
    }

    public function isElectric(): bool
    {
        return $this !== self::NonElectric;
    }
}