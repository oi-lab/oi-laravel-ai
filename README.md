<img src="./assets/github-preview.png" alt="OI Laravel AI" width="100%" />

# OI Laravel AI Add-on

[![Latest Version on Packagist](https://img.shields.io/packagist/v/oi-lab/oi-laravel-ai.svg)](https://packagist.org/packages/oi-lab/oi-laravel-ai)
[![Total Downloads](https://img.shields.io/packagist/dt/oi-lab/oi-laravel-ai.svg)](https://packagist.org/packages/oi-lab/oi-laravel-ai)
[![Tests](https://img.shields.io/github/actions/workflow/status/oi-lab/oi-laravel-ai/tests.yml?label=tests)](https://github.com/oi-lab/oi-laravel-ai/actions)
[![License](https://img.shields.io/github/license/oi-lab/oi-laravel-ai)](LICENSE)

The AI backend for your Laravel application. It maintains a provider/model catalog with pricing, records the token usage of every agent call, reports cost per project / agent / model, and centralizes your agents' system prompts.

## Features

- **Provider & model catalog** — `AiProvider` / `AiModel` tables with per-1M-token input/output pricing, seeded from a JSON registry shipped with the package.
- **Automatic usage tracking** — a listener records every `Laravel\Ai\Events\AgentPrompted` event into `ai_requests` (tokens, model, provider, project), with zero host code.
- **Cost reporting** — `AiUsageReporter` aggregates usage into per-project, per-agent, and per-model cost summaries over any period.
- **Editable prompts** — the application and its packages declare their prompts in the `PromptRegistry`; `Prompts::render()` reads the team override, then the global one, then the default, through the `SettingStore`, and fills the `:variable` placeholders.
- **Self-updating tariffs** — `php artisan ai:update-registry` fetches a fresh catalog and pricing from a remote URL and persists it locally.
- **Host-agnostic** — resolves the host's `Project` / `AgentRun` models through config and persists settings through a `SettingStore` contract.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- [`laravel/ai`](https://github.com/laravel/ai) 0.8 to 0.10

## Installation

```bash
composer require oi-lab/oi-laravel-ai
```

The package auto-discovers and registers itself via Laravel's service provider mechanism.

### Assisted installation (recommended)

Run the interactive installer and answer the prompts — it publishes the config, captures the host models / setting store / registry into your `.env`, runs the migrations, and seeds the catalog. Every step is confirmed, so it is safe to re-run:

```bash
php artisan ai:install
```

### Manual installation

```bash
php artisan vendor:publish --tag=oi-laravel-ai-config
php artisan vendor:publish --tag=oi-laravel-ai-migrations
php artisan migrate
```

> The `ai_requests` table always has nullable, indexed `project_id` and `agent_run_id` UUID columns. They only get a foreign key when you declare the matching table in `relations.project` / `relations.agent_run` **and** that table exists when you migrate. With the default (`null`) the package installs without any `projects` or `agent_runs` table.

> **Upgrading from 1.2.x:** set `relations.project` to `projects` and `relations.agent_run` to `agent_runs` in your published config (or `OI_AI_PROJECT_TABLE` / `OI_AI_AGENT_RUN_TABLE`) to keep the previous schema and the `AiRequest::project()` / `agentRun()` relations.

## Configuration

`config/oi-laravel-ai.php`:

| Key | Description |
|-----|-------------|
| `relations.project` | Table `ai_requests.project_id` references (foreign key and `AiRequest::project()` relation). `null` (default) disables both. |
| `relations.agent_run` | Same for `ai_requests.agent_run_id` and `AiRequest::agentRun()`. |
| `models.project` | Host `Project` model `AiRequest::project()` resolves to, when `relations.project` is set. |
| `models.agent_run` | Host `AgentRun` model `AiRequest::agentRun()` resolves to, when `relations.agent_run` is set. |
| `store_payloads` | `true` (default) stores system prompt, input and response; `false` redacts all of them. |
| `redacted_prompt_types` | Prompt types whose payloads are redacted (`*` wildcards allowed, e.g. `crm.*`). |
| `setting_store` | Class implementing `SettingStore`, used to persist default models and prompt overrides. Leave `null` to auto-detect: the `oi-lab/oi-laravel-settings` adapter is wired automatically when that package is installed. |
| `context_binding` | Container binding resolving the "current team" used to scope settings. |
| `registry.path` | Writable, host-owned registry copy used by the seeder and `ai:update-registry`. Falls back to the bundled `assets/registry.json` when null. |
| `registry.url` | Canonical raw URL `ai:update-registry` fetches a fresh registry from. |

All keys read from environment variables (`OI_AI_PROJECT_TABLE`, `OI_AI_AGENT_RUN_TABLE`, `OI_AI_STORE_PAYLOADS`, `OI_AI_PROJECT_MODEL`, `OI_AI_SETTING_STORE`, `OI_AI_REGISTRY_PATH`, `OI_AI_REGISTRY_URL`, …).

## Usage

### Catalog

```php
use OiLab\OiLaravelAi\Support\AiCatalog;

$registry = AiCatalog::read();   // bundled asset or host-configured path
$counts = AiCatalog::sync($registry); // upserts providers + models
// ['providers' => 5, 'models' => 15]
```

Seed the catalog (and, when a `SettingStore` is bound, the default models + prompts) from your `DatabaseSeeder`:

```php
$this->call(\OiLab\OiLaravelAi\Database\Seeders\AiCatalogSeeder::class);
```

### Usage tracking

Tracking is automatic. Once the package is installed, every agent prompt dispatched through `laravel/ai` is recorded:

```php
use OiLab\OiLaravelAi\Models\AiRequest;

AiRequest::query()->where('prompt_type', 'DocWriterAgent')->count();
```

### Cost reporting

```php
use OiLab\OiLaravelAi\Services\AiUsageReporter;

$summary = app(AiUsageReporter::class)->summaryForProjects(
    projectIds: [$project->id],
    start: now()->startOfMonth(),
    end: now()->endOfMonth(),
);

$summary->estimated_cost_usd; // float, rounded to 4 decimals
$summary->by_agent_type;      // [['prompt_type' => ..., 'count' => ..., ...], ...]
$summary->by_model;           // [['model_name' => ..., 'count' => ..., ...], ...]
```

### Editable prompts

```php
use OiLab\OiLaravelAi\Data\AiPromptVariableData;
use OiLab\OiLaravelAi\Data\PromptDefinition;
use OiLab\OiLaravelAi\Facades\Prompts;
use OiLab\OiLaravelAi\Support\PromptRegistry;

// In a service provider's boot(): declare the prompt and its default template.
app(PromptRegistry::class)->define(new PromptDefinition(
    key: 'desk.ticket_analyse.system',
    label: 'Analyse d\'un ticket',
    default: "Tu analyses les tickets de :app_name.\nTypes :\n:ticket_types",
    variables: [new AiPromptVariableData('ticket_types', 'Types (code : nom)', '- BUG : Bug')],
    group: 'Desk',
));

// In the agent: the team override, else the global one, else the default.
public function instructions(): string
{
    return Prompts::render('desk.ticket_analyse.system', ['ticket_types' => $types], teamId: $this->teamId);
}
```

`Prompts::template()`, `source()`, `save()`, `reset()`, `preview()` and `fingerprint()` back a settings screen. A definition with `teamOverridable: false` only has a global version. `AiPromptVariableRegistry` is deprecated.

## Commands

```bash
# Assisted, interactive installation (publish, configure, migrate, seed).
php artisan ai:install [--force]

# Refresh the catalog + pricing from the configured remote registry URL.
php artisan ai:update-registry [--url=https://…] [--no-write]
```

## Customizing host models

The package never hardcodes your domain models. Override the bindings in config so `AiRequest` belongs to your own `Project` / `AgentRun`:

```php
'models' => [
    'project' => \App\Models\Project::class,
    'agent_run' => \App\Models\AgentRun::class,
],
```

Settings (default models, prompt overrides) are persisted through the `SettingStore` contract. Install the recommended `oi-lab/oi-laravel-settings` (listed under `suggest`) and its adapter is wired automatically. To use your own storage, implement `OiLab\OiLaravelAi\Contracts\SettingStore` and point `setting_store` at it.

## Testing

```bash
composer test
```

## AI Assistant Skills

This package ships a skill that teaches AI coding assistants (Claude Code, JetBrains AI) how to use it. The recommended way to install it is the unified `oi:skills` command from `oi-lab/oi-laravel-development`:

```bash
php artisan oi:skills oilab-laravel-ai --project
```

A deprecated package-local fallback is available for projects that don't use `oi-lab/oi-laravel-development`:

```bash
php artisan oi-ai:install-ai-skill
```

See [docs/advanced/skills.md](docs/advanced/skills.md) for details.

## Documentation

The `docs/` folder is importable into any app using [`oi-lab/oi-laravel-documentation`](https://packagist.org/packages/oi-lab/oi-laravel-documentation):

```bash
php artisan doc:import
```

## License

The MIT License (MIT). See [LICENSE](LICENSE).

## Credits

**[Olivier Lacombe](https://www.olacombe.com)** - Creator and maintainer

Olivier is a Product & Technology Director based in Montpellier, France, with over 20 years of experience innovating in UX/UI and emerging technologies. He specializes in guiding enterprises toward cutting-edge digital solutions, combining user-centered design with continuous optimization and artificial intelligence integration.

**Projects & Resources:**
- [OI Dev Docs](https://dev.olacombe.com) - Documentation for all Open Source OI Lab packages
- [OnAI](https://onai.olacombe.com) - Training courses and masterclasses on generative AI for businesses
- [Promptr](https://promptr.olacombe.com) - Prompt engineering Management Platform

## Support

For support, please open an issue on the [GitHub repository](https://github.com/oi-lab/oi-laravel-ai/issues).
