---
title: Configuration
description: Every key in config/oi-laravel-ai.php
section: configuration
order: 1
---

# Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=oi-laravel-ai-config
```

## Host application models

```php
'models' => [
    'project' => env('OI_AI_PROJECT_MODEL', \App\Models\Project::class),
    'agent_run' => env('OI_AI_AGENT_RUN_MODEL', \App\Models\AgentRun::class),
],
```

`AiRequest` references your application's `Project` and `AgentRun` models through UUID foreign keys. Override these so the package adapts to your domain without being coupled to it. The `project()` and `agentRun()` relations resolve through these bindings at runtime.

## Setting store

```php
'setting_store' => env('OI_AI_SETTING_STORE', \App\Adapters\AiSettingStore::class),
```

The package never persists settings itself — it delegates to your implementation of `OiLab\OiLaravelAi\Contracts\SettingStore`. Set this to `null` to disable settings seeding entirely; catalog seeding still works. See [Host integration](../advanced/host-integration.md).

## Team context binding

```php
'context_binding' => 'current.team',
```

The container binding resolving the "current team" used to scope settings. Your application is responsible for binding it.

## Catalog registry

```php
'registry' => [
    'path' => env('OI_AI_REGISTRY_PATH'),
    'url' => env('OI_AI_REGISTRY_URL'),
],
```

- `path` — a writable, host-owned copy of the registry used by the seeder and the `ai:update-registry` command. When `null`, the bundled `assets/registry.json` is used (read-only).
- `url` — the canonical raw location `ai:update-registry` fetches a fresh registry from.

## Environment variables

| Variable | Config key |
|----------|------------|
| `OI_AI_PROJECT_MODEL` | `models.project` |
| `OI_AI_AGENT_RUN_MODEL` | `models.agent_run` |
| `OI_AI_SETTING_STORE` | `setting_store` |
| `OI_AI_REGISTRY_PATH` | `registry.path` |
| `OI_AI_REGISTRY_URL` | `registry.url` |
