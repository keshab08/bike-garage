# Bike Garage Coding Challenge

A small full-stack coding challenge. Set up a fresh
[`symfony/skeleton`](https://symfony.com/doc/current/setup.html) app, then build
the feature below around the provided `bikes.json`.

You'll import some messy data, model it cleanly, and put a usable UI on top.
There's **no single right answer** - the data has a few intentional ambiguities,
and we're more interested in the decisions you make (and why) than in you
guessing a "correct" one. A short note on each trade-off is worth more than a
perfect-looking guess.

**Timebox: 3–4 hours.** A finished small slice beats a large unfinished one.
If you run short on time, prioritise the domain model, the normalisation
service, and its tests over UI polish.

---

## Setup

Create a fresh Symfony skeleton and add what you need:

```bash
composer create-project symfony/skeleton bike-garage
cd bike-garage
composer require twig form validator # add what you use
```

Copy `bikes.json` into the project (e.g. `data/bikes.json`). Run the app however
you like - the Symfony CLI (`symfony server:start`) or your own Docker setup,
your call.

---

## Persistence model - please read

This challenge is about **logic and modelling, not databases**. So:

- **No database, no ORM, no migrations.** Don't reach for Doctrine.
- **`bikes.json` is your store.** The service reads and normalises it at
  runtime. Adding a bike (task 4) **appends the new, normalised record back to
  `bikes.json`**, so it survives the next request and shows up in the list.

That's the whole persistence story: one JSON file, read on load, written on add.
Note the consequence - your reader must cope with **both** the messy original
records **and** the clean records your own "add" writes back. Keep the
read/normalise path idempotent.

---

## The task

A dataset of ~30 bike listings lives in `bikes.json`. Build a feature that lets
a user browse and add bikes.

1. **Load & normalise** - a service that reads `bikes.json` at runtime and turns
   it into your domain objects. The data is intentionally messy; normalise it
   into clean, typed values. 

2. **List page** - render all bikes, with **filtering by category** and
   **sorting by price**. Show the **discount** (original vs current price).

3. **Detail page** - a single bike. Note that `bikes.json` has **no id field**;
   deciding how a bike is identified in the URL is part of the task. Pick a
   stable, collision-safe scheme and be ready to explain it.

4. **Add page** - a form to create a bike, with **validation**, that appends the
   normalised record to `bikes.json` (see persistence model above). 

---

## Bonus (optional)

- Search by brand or model
- A small Twig extension (e.g. price/battery formatting)
- A bit of styling

---

## What we look for

- A clean, typed domain model - the messy JSON should not leak past the service
  boundary.
- Sensible handling of the ambiguities above, with your reasoning noted.
- KISS, DRY, YAGNI, SOLID applied.
- Logic in services, not controllers or templates.
- **The normalisation logic covered by tests** - including the tricky cases
  (odd casing, missing battery, thousands separators).
- Respect the timebox - we expect 3–4 hours, not a weekend.

---

## Submitting

Please upload your code to a public git repository (GitHub, GitLab, …). In your
README, add a few lines on:

- what you'd do next with more time,
- the trade-offs and assumptions you made 
