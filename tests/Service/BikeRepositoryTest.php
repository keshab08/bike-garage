<?php

namespace App\Tests\Service;

use App\Model\Category;
use App\Service\BikeIdGenerator;
use App\Service\BikeNormalizer;
use App\Service\BikeRepository;
use PHPUnit\Framework\TestCase;

final class BikeRepositoryTest extends TestCase
{
    private string $storePath;

    protected function setUp(): void
    {
        $this->storePath = tempnam(sys_get_temp_dir(), 'bikes') . '.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->storePath);
    }

    private function record(array $overrides = []): array
    {
        return array_merge([
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
    }

    private function repository(array $records): BikeRepository
    {
        file_put_contents($this->storePath, json_encode($records));

        return new BikeRepository(new BikeNormalizer(), new BikeIdGenerator(), $this->storePath);
    }

    public function testBackfillsMissingIdsWithPrefixSchemeInFileOrder(): void
    {
        $repository = $this->repository([
            $this->record(['category' => 'e-trekking']),
            $this->record(['category' => 'road']),
            $this->record(['category' => 'e-mtb']),
        ]);

        $ids = array_map(fn ($bike) => $bike->id, $repository->all());

        // The counter is shared across prefixes, so the number keeps
        // climbing regardless of which category comes next.
        $this->assertSame(['E900000001', 'R900000002', 'E900000003'], $ids);
    }

    public function testNumbersAreNeverReusedAcrossDifferentPrefixes(): void
    {
        $repository = $this->repository([
            $this->record(['category' => 'e-trekking']),
            $this->record(['category' => 'road']),
        ]);

        $ids = array_map(fn ($bike) => $bike->id, $repository->all());
        $numbers = array_map(fn (string $id) => substr($id, -7), $ids);

        $this->assertSame(['E900000001', 'R900000002'], $ids);
        $this->assertSame(count($numbers), count(array_unique($numbers)));
    }

    public function testBackfilledIdsArePersistedAndStableAcrossReads(): void
    {
        $repository = $this->repository([
            $this->record(['category' => 'road']),
        ]);

        $firstRead = $repository->all()[0]->id;

        // A fresh repository instance re-reading the same file must see
        // the same id - it was written back, not recomputed from scratch.
        $repositoryAgain = new BikeRepository(new BikeNormalizer(), new BikeIdGenerator(), $this->storePath);
        $secondRead = $repositoryAgain->all()[0]->id;

        $this->assertSame($firstRead, $secondRead);
        $this->assertSame('R900000001', $firstRead);
    }

    public function testExistingIdsAreLeftUntouchedAndCountedForTheIncrement(): void
    {
        $repository = $this->repository([
            ['id' => 'R900000005', ...$this->record(['category' => 'road'])],
            $this->record(['category' => 'road']),
        ]);

        $ids = array_map(fn ($bike) => $bike->id, $repository->all());

        $this->assertSame(['R900000005', 'R900000006'], $ids);
    }

    public function testNextIdContinuesTheSequenceForNewBikes(): void
    {
        $repository = $this->repository([
            $this->record(['category' => 'road']),
        ]);

        $repository->all(); // trigger backfill: existing bike becomes R900000001

        $this->assertSame('R900000002', $repository->nextId(Category::Road));
    }

    public function testSharedEPrefixIsCountedAcrossElectricCategories(): void
    {
        $repository = $this->repository([
            $this->record(['category' => 'e-trekking']),
            $this->record(['category' => 'e-mtb']),
        ]);

        $this->assertSame('E900000003', $repository->nextId(Category::ECitybike));
    }
}
