<?php

namespace App\Tests\Service;

use App\Model\Bike;
use App\Model\Category;
use App\Model\Condition;
use App\Model\DriveType;
use App\Service\BikeNormalizer;
use PHPUnit\Framework\TestCase;

final class BikeNormalizerTest extends TestCase
{
    /**
     * A complete bike record in the messy format of the original JSON.
     * Pass overrides to change just the field a test cares about, e.g.
     * $this->normalize(['battery' => null]).
     */
    private function normalize(array $overrides = []): Bike
    {
        $record = array_merge([
            'id' => 'test-id',
            'brand' => 'Cube',
            'model' => 'Kathmandu Hybrid ONE 625',
            'category' => 'e-trekking',
            'drive_type' => 'Pedelec',
            'battery' => '625 Wh',
            'frame_size' => '54 cm',
            'mileage' => '1,240 km',
            'original_price' => '€3,618.95',
            'current_price' => '€1,929.00',
            'condition' => 'Very good',
        ], $overrides);

        return (new BikeNormalizer())->normalize($record);
    }

    // --- Prices -----------------------------------------------------

    // "€3,618.95" must lose the symbol and comma, and become integer cents.
    public function testPriceWithСurrencyAndThousandsSeparatorBecomesCents(): void
    {
        $bike = $this->normalize();

        $this->assertSame(361895, $bike->originalPriceCents);
        $this->assertSame(192900, $bike->currentPriceCents);
    }

    // A price we wrote ourselves is already cents - it must not be multiplied again.
    public function testPriceThatIsAlreadyCentsIsLeftAlone(): void
    {
        $bike = $this->normalize(['original_price' => 361895]);

        $this->assertSame(361895, $bike->originalPriceCents);
    }

    // --- Battery ----------------------------------------------------

    public function testBatteryWithSpaceBeforeUnit(): void
    {
        $this->assertSame(625, $this->normalize(['battery' => '625 Wh'])->batteryWh);
    }

    public function testBatteryWithoutSpaceBeforeUnit(): void
    {
        $this->assertSame(625, $this->normalize(['battery' => '625Wh'])->batteryWh);
    }

    // A non-electric bike has no battery at all - that stays null, not 0.
    public function testMissingBatteryStaysNull(): void
    {
        $this->assertNull($this->normalize(['battery' => null])->batteryWh);
    }

    public function testBatteryThatIsAlreadyANumberIsLeftAlone(): void
    {
        $this->assertSame(625, $this->normalize(['battery' => 625])->batteryWh);
    }

    // --- Mileage ----------------------------------------------------

    public function testMileageLosesThousandsSeparatorAndUnit(): void
    {
        $this->assertSame(1240, $this->normalize(['mileage' => '1,240 km'])->mileageKm);
    }

    // The important edge case: a brand-new bike really has 0 km.
    // 0 means "zero kilometres", null means "we don't know" - different things.
    public function testZeroMileageIsZeroAndNotNull(): void
    {
        $this->assertSame(0, $this->normalize(['mileage' => '0 km'])->mileageKm);
    }

    public function testMissingMileageStaysNull(): void
    {
        $this->assertNull($this->normalize(['mileage' => null])->mileageKm);
    }

    // --- Casing -----------------------------------------------------

    // The JSON is inconsistent about capitals; both spellings must land
    // on the same enum case.
    public function testCategoryAcceptsCapitalisedSpelling(): void
    {
        $this->assertSame(Category::ETrekking, $this->normalize(['category' => 'E-Trekking'])->category);
    }

    public function testCategoryAcceptsLowercaseSpelling(): void
    {
        $this->assertSame(Category::ETrekking, $this->normalize(['category' => 'e-trekking'])->category);
    }

    public function testConditionAcceptsCapitalisedSpelling(): void
    {
        $this->assertSame(Condition::VeryGood, $this->normalize(['condition' => 'Very good'])->condition);
    }

    public function testConditionAcceptsLowercaseSpelling(): void
    {
        $this->assertSame(Condition::VeryGood, $this->normalize(['condition' => 'very good'])->condition);
    }

    // "E-Bike" and "Pedelec" mean the same thing; we store one of them.
    public function testEBikeIsStoredAsPedelec(): void
    {
        $this->assertSame(DriveType::Pedelec, $this->normalize(['drive_type' => 'E-Bike'])->driveType);
    }

    // --- Id ---------------------------------------------------------

    // The id is assigned by the repository, so the normalizer just passes it on.
    public function testIdIsPassedThroughUnchanged(): void
    {
        $this->assertSame('test-id', $this->normalize()->id);
    }
}