<?php

namespace App\Service;

final class BikeIdGenerator
{
    private const MIDDLE = '90';
    private const PAD = 7; // digits after "90" -> 0000001

    /**
     * The number after "90" is a single counter shared across every
     * prefix, not one counter per prefix - so no two bikes ever end up
     * with the same number, even under different category letters.
     *
     * @param string[] $existingIds all ids already in use, any prefix
     */
    public function next(string $prefix, array $existingIds): string
    {
        $highest = 0;

        foreach ($existingIds as $id) {
            $number = (int) substr($id, -self::PAD);
            if ($number > $highest) {
                $highest = $number;
            }
        }

        $next = $highest + 1;

        return $prefix . self::MIDDLE . str_pad((string) $next, self::PAD, '0', STR_PAD_LEFT);
    }
}