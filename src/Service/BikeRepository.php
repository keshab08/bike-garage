<?php
namespace App\Service;

use App\Model\Bike;
use App\Model\Category;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class BikeRepository
{
    public function __construct(
        private readonly BikeNormalizer $normalizer,
        private readonly BikeIdGenerator $idGenerator,
        #[Autowire('%kernel.project_dir%/data/bikes.json')]
        private readonly string $storePath,
    ) {}

    /** @return Bike[] */
    // This method retrieves all bikes from the repository, optionally filtering by category, sorting by price, and searching by a query string. It returns an array of Bike objects.
    public function all(?Category $category = null, ?string $sort = null, ?string $searchQuery = null): array
    {
        // Map the raw records to Bike objects using the normalizer
        $bikes = array_map(
            fn (array $raw): Bike => $this->normalizer->normalize($raw),
            $this->recordsWithIds(),
        );
// Filter by category if provided
        if ($category !== null) {
            $bikes = array_values(array_filter(
                $bikes,
                fn (Bike $bike): bool => $bike->category === $category,
            ));
        }
// Filter by search query if provided
        if ($searchQuery !== null) {
            $needle = strtolower($searchQuery);
            $bikes = array_values(array_filter(
                $bikes,
                fn (Bike $bike): bool => $this->matchesSearch($bike, $needle),
            ));
        }
// Sort the bikes by price if a sort option is provided
        if ($sort === 'price_asc') {
            usort($bikes, fn (Bike $a, Bike $b): int =>
                $a->currentPriceCents <=> $b->currentPriceCents);
        } elseif ($sort === 'price_desc') {
            usort($bikes, fn (Bike $a, Bike $b): int =>
                $b->currentPriceCents <=> $a->currentPriceCents);
        }

        return $bikes;
    }
// This method finds a bike by its ID. It returns the Bike object if found, or null if not found.
    public function find(string $id): ?Bike
    {
        foreach ($this->all() as $bike) {
            if ($bike->id === $id) {
                return $bike;
            }
        }

        return null;
    }
// This method adds a new bike to the repository. It converts the Bike object to a record format and writes it to the data store.
    public function add(Bike $bike): void
    {
        $records = $this->recordsWithIds();
        $records[] = $this->toRecord($bike);
        $this->writeRaw($records);
    }

// This method generates the next unique ID for a new bike based on its category. It ensures that the ID is unique by checking against existing IDs in the repository.
    public function nextId(Category $category): string
    {
        $existingIds = array_column($this->recordsWithIds(), 'id');

        return $this->idGenerator->next($category->idPrefix(), $existingIds);
    }
// This method reads the raw bike data from the JSON file and returns it as an array. It uses JSON decoding to convert the JSON string into a PHP array.
    private function readRaw(): array
    {
        $json = file_get_contents($this->storePath);

        return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

// This method ensures that all bike records have unique IDs. If any records are missing an ID, 
// it generates a new ID for them based on their category and updates the records accordingly. It then writes the updated records back to the data store.
    private function recordsWithIds(): array
    {
        $records = $this->readRaw();

        $missingAny = false;
        foreach ($records as $record) {
            if (!isset($record['id'])) {
                $missingAny = true;
                break;
            }
        }

        if (!$missingAny) {
            return $records;
        }

        $ids = array_values(array_filter(array_column($records, 'id')));

        foreach ($records as &$record) {
            if (isset($record['id'])) {
                continue;
            }

            $prefix = Category::fromRaw($record['category'])->idPrefix();
            $record['id'] = $this->idGenerator->next($prefix, $ids);
            $ids[] = $record['id'];
        }
        unset($record);

        $this->writeRaw($records);

        return $records;
    }
// This method writes the given array of bike records to the JSON file. 
// It encodes the records as a JSON string and writes it to a temporary file first, then renames it to the actual store path to ensure atomicity.
    private function writeRaw(array $records): void
    {
        $json = json_encode(
            $records,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        // Write to a temp file first, then rename: rename is atomic,
        // so a crash mid-write can never leave a half-written store.
        $tmp = $this->storePath . '.tmp';
        file_put_contents($tmp, $json);
        rename($tmp, $this->storePath);
    }
    
    /**
     * The clean, canonical format our add form writes. Same keys as the
     * original file, but typed values. The normalizer's "already clean"
     * branches exist exactly for these records.
     */
    private function toRecord(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'brand' => $bike->brand,
            'model' => $bike->model,
            'category' => $bike->category->value,
            'drive_type' => $bike->driveType->value,
            'battery' => $bike->batteryWh,
            'frame_size' => $bike->frameSize,
            'mileage' => $bike->mileageKm,
            'original_price' => $bike->originalPriceCents,
            'current_price' => $bike->currentPriceCents,
            'condition' => $bike->condition->value,
        ];
    }
// This method checks if a given bike matches a search query. It looks for the query string in the bike's brand, model, and category (both label and value).
    private function matchesSearch(Bike $bike, string $needle): bool
    {
        $haystacks = [
            strtolower($bike->brand),
            strtolower($bike->model),
            strtolower($bike->category->label()),
            strtolower($bike->category->value),
        ];

        foreach ($haystacks as $haystack) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}