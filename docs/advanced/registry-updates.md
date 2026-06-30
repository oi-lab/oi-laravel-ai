---
title: Updating the registry
description: Refresh the catalog and pricing from a remote URL
section: advanced
order: 3
---

# Updating the registry

Model pricing changes over time. The `ai:update-registry` command fetches a fresh provider/model catalog from a remote URL, upserts it into the database, and (optionally) persists the file locally for future offline seeds.

## Configure the source

```php
// config/oi-laravel-ai.php
'registry' => [
    'path' => env('OI_AI_REGISTRY_PATH', storage_path('app/ai-registry.json')),
    'url' => env('OI_AI_REGISTRY_URL', 'https://raw.githubusercontent.com/oi-lab/oi-laravel-ai/main/assets/registry.json'),
],
```

## Run the command

```bash
php artisan ai:update-registry
```

It will:

1. Fetch the registry JSON from `registry.url` (or `--url`).
2. Validate and decode it (`providers` and `models` keys are required).
3. Upsert providers and models into the database.
4. Save the downloaded file to `registry.path`, if that location is writable.

## Options

| Option | Effect |
|--------|--------|
| `--url=<url>` | Override the configured registry URL for this run. |
| `--no-write` | Upsert the catalog without persisting the downloaded file. |

x> If `registry.path` is not writable, the catalog is still upserted but the file is not saved, and the command warns you. Point `registry.path` at a writable, host-owned location to enable offline re-seeding.

## Scheduling

Refresh pricing periodically from your console schedule:

```php
// routes/console.php
Schedule::command('ai:update-registry')->weekly();
```
