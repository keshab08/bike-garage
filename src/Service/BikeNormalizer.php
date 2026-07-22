<?php

namespace App\Service;

use App\Model\Bike;
use App\Model\Category;
use App\Model\Condition;
use App\Model\DriveType;

final class BikeNormalizer
{
    public function normalize(array $raw): Bike
    {
        return new Bike(
            id: $raw['id'],
            brand: trim($raw['brand']),
            model: trim($raw['model']),
            category: Category::fromRaw($raw['category']),
            driveType: DriveType::fromRaw($raw['drive_type']),
            batteryWh: $this->parseLeadingInt($raw['battery'] ?? null),
            frameSize: trim($raw['frame_size']),
            mileageKm: $this->parseLeadingInt($raw['mileage'] ?? null),
            originalPriceCents: $this->parsePriceCents($raw['original_price']),
            currentPriceCents: $this->parsePriceCents($raw['current_price']),
            condition: Condition::fromRaw($raw['condition']),
        );
    }

    /**
     * Accepts "€3,618.95" (messy original) or 361895 (already-clean
     * record written by our own add form). Returns cents as int.
     */
    private function parsePriceCents(int|string $value): int
    {
        if (is_int($value)) {
            return $value; // already clean -> idempotent
        }

        $cleaned = str_replace(['€', ',', ' '], '', $value); // "3618.95"

        return (int) round((float) $cleaned * 100);
    }

    /**
     * Accepts "625 Wh", "625Wh", "1,240 km", "0 km", 625, or null.
     * Returns the number, or null when there is no number at all.
     */
    private function parseLeadingInt(int|string|null $value): ?int
    {
        if ($value === null || is_int($value)) {
            return $value; // null stays null, clean int passes through
        }

        $digits = preg_replace('/\D/', '', $value); // keep digits only

        if ($digits === '') {
            return null;
        }

        return (int) $digits;
    }
}