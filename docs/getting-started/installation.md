---
title: Installation
description: How to install and migrate OI Laravel AI
section: getting-started
order: 2
---

# Installation

## Via Composer

```bash
composer require oi-lab/oi-laravel-ai
```

The package auto-discovers and registers its service provider — no manual registration required.

## Assisted installation (recommended)

The interactive installer walks you through every step below — publishing the config, capturing the host models, setting store and registry URL into your `.env`, running the migrations, and seeding the catalog. Each step is confirmed, so it is safe to re-run:

```bash
php artisan ai:install
```

Pass `--force` to overwrite already-published files. Prefer the manual steps below if you want full control.

## Publish configuration and migrations

```bash
php artisan vendor:publish --tag=oi-laravel-ai-config
php artisan vendor:publish --tag=oi-laravel-ai-migrations
```

This creates `config/oi-laravel-ai.php` and copies the catalog and request migrations into your application.

## Prepare host tables

The `ai_requests` table declares nullable foreign keys to your `projects` and `agent_runs` tables (UUID primary keys):

```php
$table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();
$table->foreignUuid('agent_run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
```

i> Make sure those tables exist before migrating. If your application uses different tables or key types, edit the published migration before running it.

## Migrate

```bash
php artisan migrate
```

## Seed the catalog

Add the seeder to your `DatabaseSeeder` to populate providers, models, and pricing:

```php
$this->call(\OiLab\OiLaravelAi\Database\Seeders\AiCatalogSeeder::class);
```

```bash
php artisan db:seed
```

## Verify the installation

```bash
php artisan tinker
>>> OiLab\OiLaravelAi\Models\AiModel::count();
```

You should see the models from the bundled registry. Continue with [Configuration](../configuration/configuration.md).
