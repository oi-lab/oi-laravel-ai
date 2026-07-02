# OI Laravel AI — AI Context

This package is the AI backend for a Laravel application. It does four things:

1. **Catalog** — a provider/model catalog with per-1M-token pricing, persisted in the database and seeded from a JSON registry (`assets/registry.json`).
2. **Usage tracking** — listens to `Laravel\Ai\Events\AgentPrompted` and records every agent call (tokens, model, provider, project) into the `ai_requests` table.
3. **Cost reporting** — aggregates recorded usage into per-project, per-agent, and per-model cost summaries.
4. **Prompt registry** — a single source of truth for the agents' system prompts, with `:variable` placeholder compilation.

The package is host-agnostic: it references the host app's `Project` and `AgentRun` models through config, and persists settings through a `SettingStore` contract — auto-wired to `oi-lab/oi-laravel-settings` when installed, or a host-provided implementation.

## Core concepts

- **AiProvider** (`ai_providers`) — `code`, `name`, `owner`. Has many `AiModel`.
- **AiModel** (`ai_models`) — `code`, `name`, `type` (`text`/`embeddings`), `cost_input`, `cost_output` (USD per 1M tokens), `ai_provider_id`.
- **AiRequest** (`ai_requests`) — one row per agent call: `prompt_type`, token counts, `duration`, and nullable FKs to `project`, `agent_run`, `ai_provider`, `ai_model`. `created_at` only (no `updated_at`).

Models live in `OiLab\OiLaravelAi\Models`. The `project()` and `agentRun()` relations resolve through `config('oi-laravel-ai.models.*')` — never hardcode the host model class.

## Public API

```php
use OiLab\OiLaravelAi\Support\AiCatalog;
use OiLab\OiLaravelAi\Support\AiPromptVariableRegistry;
use OiLab\OiLaravelAi\Services\AiUsageReporter;

// Read + upsert the provider/model catalog from the JSON registry.
$registry = AiCatalog::read();          // bundled asset, or the host-configured path
AiCatalog::sync($registry);             // upserts providers + models, returns counts

// Compile an agent system prompt, substituting :variable placeholders.
$prompt = AiPromptVariableRegistry::compile(
    AiPromptVariableRegistry::defaultPrompt(AiPromptVariableRegistry::DOC_WRITER),
    ['project_name' => 'Acme', 'language' => 'français'],
);

// Aggregate cost/usage for a set of projects over a period (defaults to current month).
$summary = app(AiUsageReporter::class)->summaryForProjects($projectIds, $start, $end);
$summary->estimated_cost_usd; // float
```

`AiPromptVariableRegistry` exposes a constant per agent (`COMMIT_ANALYZER`, `DOC_WRITER`, `DOC_REVIEWER`, …), `keys()`, `label()`, `defaultPrompt()`, `variablesFor()`, `globals()`, and `compile()`. The `app_name` global is injected automatically at compile time.

## Usage tracking

The service provider wires `AiRequestListener` to `Laravel\Ai\Events\AgentPrompted`. Every agent prompt is recorded automatically — no host code required. The listener is fully guarded: a tracing failure logs a warning and never breaks the main flow. It maps the response's provider/model codes back to the catalog rows.

## Commands

```bash
# Refresh the catalog + pricing from the remote registry URL and persist it locally.
php artisan ai:update-registry [--url=...] [--no-write]

# Install the AI assistant skill for this package (deprecated — prefer `oi:skills`).
php artisan oi-ai:install-ai-skill
```

## Seeding

`OiLab\OiLaravelAi\Database\Seeders\AiCatalogSeeder` syncs the catalog and, when a `SettingStore` is bound, seeds the default system prompts. Call it from the host's `DatabaseSeeder`. The registry is a generic providers/models/pricing catalog — it does not carry per-agent model selection; keep that mapping in the host application's settings.

## Configuration

```bash
php artisan vendor:publish --tag=oi-laravel-ai-config
php artisan vendor:publish --tag=oi-laravel-ai-migrations
```

Key `oi-laravel-ai` config:

- `models.project` / `models.agent_run` — host model classes the `AiRequest` FKs resolve to.
- `setting_store` — `OiLab\OiLaravelAi\Contracts\SettingStore` implementation (class string). Leave `null` to auto-detect: the `oi-lab/oi-laravel-settings` adapter is wired automatically when installed (listed under `suggest`); with no store bound, catalog seeding still runs but settings seeding is skipped.
- `context_binding` — container binding resolving the "current team" used to scope settings.
- `registry.path` — writable, host-owned copy of the registry used by the seeder and `ai:update-registry`; falls back to the bundled asset when null.
- `registry.url` — canonical raw URL `ai:update-registry` fetches a fresh registry from.

## Host integration checklist

- Provide `projects` and `agent_runs` tables (UUID keys) before running the package migrations — the `ai_requests` FKs reference them.
- Install `oi-lab/oi-laravel-settings` for zero-config settings, or implement `SettingStore` and point `setting_store` at it (or leave it `null`).
- Bind the `context_binding` if you scope settings per team.

## Updating the AI skill

After updating this package, re-install the skill files:

```bash
php artisan oi:skills oilab-laravel-ai --project
# or the deprecated package-local fallback:
php artisan oi-ai:install-ai-skill
```
