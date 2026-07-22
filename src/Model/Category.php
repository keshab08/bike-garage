<?php

namespace App\Model;

enum Category: string
{
    case ETrekking = 'e-trekking';
    case EMtb = 'e-mtb';
    case ECitybike = 'e-citybike';
    case SPedelec = 's-pedelec';
    case Compact = 'compact';
    case Road = 'road';
    case Trekking = 'trekking';
    case Mtb = 'mtb';
    case Citybike = 'citybike';

    public static function fromRaw(string $raw): self
    {
        return self::from(mb_strtolower(trim($raw)));
    }

    public function label(): string
    {
        return match ($this) {
            self::ETrekking => 'E-Trekking',
            self::EMtb      => 'E-MTB',
            self::ECitybike => 'E-Citybike',
            self::SPedelec  => 'S-Pedelec',
            self::Compact   => 'Compact',
            self::Road      => 'Road',
            self::Trekking  => 'Trekking',
            self::Mtb       => 'MTB',
            self::Citybike  => 'Citybike',
        };
    }

    public function idPrefix(): string
    {
      return match ($this) {
        self::ETrekking, self::EMtb, self::ECitybike => 'E',
        self::SPedelec   => 'SP',
        self::Compact    => 'K',
        self::Road       => 'R',
        self::Trekking   => 'T',
        self::Mtb        => 'M',
        self::Citybike   => 'C',
    };
}
}