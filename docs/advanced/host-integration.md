---
title: Host integration
description: Wire your models, implement SettingStore and seed defaults
section: advanced
order: 2
---

# Host integration

OI Laravel AI is designed to plug into an existing application without imposing its domain models. There are three integration points.

## 1. Host models

Point the config bindings at your own models:

```php
'models' => [
    'project' => \App\Models\Project::class,
    'agent_run' => \App\Models\AgentRun::class,
],
```

The `ai_requests` migration adds UUID foreign keys to `projects` and `agent_runs`. Both are nullable and `nullOnDelete`, so recording never fails when a request has no project or run.

## 2. The SettingStore contract

The package reads and writes team-scoped / global settings (default model per agent, prompt overrides) through the `SettingStore` contract. The AI team id maps directly onto a settings scope (`null` = global).

### Recommended: oi-lab/oi-laravel-settings

Install the recommended backend (listed under `suggest`) and it is **wired automatically** — no config, no adapter to write:

```bash
composer require oi-lab/oi-laravel-settings
```

When `setting_store` is left `null` and that package is present, the provider binds `OiLab\OiLaravelAi\Stores\OiLaravelSettingsStore`, persisting values in the shared, scoped, typed Setting store.

### Custom adapter

To use your own storage instead, implement the contract:

```php
namespace App\Adapters;

use OiLab\OiLaravelAi\Contracts\SettingStore;

class AiSettingStore implements SettingStore
{
    public function get(string $key, ?string $teamId = null): mixed { /* ... */ }

    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $teamId = null): void { /* ... */ }

    public function forget(string $key, ?string $teamId = null): void { /* ... */ }
}
```

Bind it through config:

```php
'setting_store' => \App\Adapters\AiSettingStore::class,
```

Resolution order: an explicit `setting_store` class wins; otherwise the oi-laravel-settings adapter is used when installed; otherwise it resolves to `null`, which simply disables the settings-dependent seeding (catalog seeding still runs).

## 3. Seeding defaults

`AiCatalogSeeder` does two things:

1. Syncs providers, models, and pricing from the registry.
2. When a `SettingStore` is bound, seeds the default system prompt for every agent in `AiPromptVariableRegistry` (keys like `PROMPT_SYSTEM.DOC_WRITER`).

Choosing which model a given agent uses is an application concern, not a catalog one — keep that mapping in your own settings, not in the registry.

```php
// database/seeders/DatabaseSeeder.php
$this->call(\OiLab\OiLaravelAi\Database\Seeders\AiCatalogSeeder::class);
```
