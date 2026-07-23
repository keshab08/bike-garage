# Bike Garage

A small Symfony app that reads a JSON file of used bikes and shows them as a
list, a detail page, and a form to add new ones.

## Setup

```bash
composer install
symfony serve
```

Open <http://localhost:8000>. The data is in `data/bikes.json` — no database
needed.

## Tests

```bash
php bin/phpunit
```

## Pages

- `/` — all bikes, with category filter, price sort and search
- `/bikes/{id}` — one bike
- `/bikes/new` — add a bike

## How it works

`BikeNormalizer` turns each raw JSON record into a clean `Bike` object: prices
become integer cents, mileage and battery become numbers, and category, drive
type and condition become enums. It accepts both the messy original format
(`"€3,618.95"`, `"1,240 km"`, `"E-Trekking"`) and the clean format the app
itself writes, so both can live in the same file.

`BikeRepository` owns the file — reading, filtering, sorting and writing.

## The id scheme

The JSON has no id field, so the app assigns one: a category prefix, then `90`,
then a 7-digit number.

```
E900000001   electric
R900000002   road
```

The number is one counter shared by all categories, so no two bikes get the same
one. Ids are written back into the file the first time it is read, which means
they never change afterwards — a detail page URL keeps working even if the bike's
price is edited later.

I first derived the id by hashing the bike's fields, but dropped it: two
identical bikes got the same id, and editing any field changed the id and broke
its URL.

## Assumptions

- One user at a time. There is no file locking.
- Only battery and mileage are optional; every other field is expected.
- `0 km` means a new bike, `null` means unknown — these are kept distinct.

## Trade-offs

- **A JSON file, not a database.** Simple and dependency-free.
- **Ids are assigned on first read**, so that one request writes to the file.
  Zero setup steps, but a `GET` with a side effect. 
- **No locking**, so two simultaneous writes could clash.
- **Tests focus on normalisation and ids**, where the real risk is. Controllers
  and templates are not tested.

## With more time

-Edit and delete the bike.
-Images per bike.
-user friendly GUI
-Integrate with Backend rather than a json as a backend
