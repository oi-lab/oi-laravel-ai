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

## Prepare host tables (optional)

The `ai_requests` table always has nullable, indexed `project_id` and `agent_run_id` UUID columns. They are only constrained by a foreign key when you declare the target table in the config and it exists at migration time:

```php
'relations' => [
    'project' => 'projects',
    'agent_run' => 'agent_runs',
],
```

i> With the default (`null`), no `projects` or `agent_runs` table is needed. Upgrading from 1.2.x? Set both keys to keep the previous schema and relations.

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
