<?php

namespace App\Model;

final class Bike
{
    public function __construct(
        public readonly string $id,
        public readonly string $brand,
        public readonly string $model,
        public readonly Category $category,
        public readonly DriveType $driveType,
        public readonly ?int $batteryWh,
        public readonly string $frameSize,
        public readonly ?int $mileageKm,
        public readonly int $originalPriceCents,
        public readonly int $currentPriceCents,
        public readonly Condition $condition,
    ) {}

    public function discountPercent(): int
    {
        if ($this->originalPriceCents <= 0) {
            return 0;
        }

        $saved = $this->originalPriceCents - $this->currentPriceCents;

        return (int) round($saved / $this->originalPriceCents * 100);
    }
}