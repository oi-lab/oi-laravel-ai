# Changelog

All notable changes to `oi-lab/oi-laravel-ai` will be documented in this file.

## [1.1.0] - 2026-07-06

### Added

- `ai:install` — an assisted, interactive installation command (built on `laravel/prompts`) that publishes the config, captures the host models / setting store / registry URL into `.env`, warns about missing `projects` / `agent_runs` tables, runs the migrations, seeds the provider/model catalog, and optionally installs the AI assistant skill. Every step is confirmed, so it is safe to re-run.
- Explicit `laravel/prompts` dependency.

## [1.0.0] - 2026-06-30

### Added

- Provider/model catalog (`AiProvider`, `AiModel`) with per-1M-token pricing, seeded from a bundled JSON registry (`assets/registry.json`).
- Automatic AI usage tracking: an `AgentPrompted` listener records every agent call into the `ai_requests` table (`AiRequest`).
- Cost reporting via `AiUsageReporter` — per-project, per-agent, and per-model summaries over a period.
- `AiPromptVariableRegistry` — single source of truth for agent system prompts with `:variable` placeholder compilation.
- `ai:update-registry` command to refresh the catalog and pricing from a remote URL.
- `AiCatalogSeeder` seeding the catalog, default models, and default prompts through the host-bound `SettingStore` contract.
- Host-agnostic model resolution (`Project` / `AgentRun`) via configuration.
- AI assistant skill (`oilab-laravel-ai`) with `oi-ai:install-ai-skill` command and `oi:skills` integration.
- Importable documentation tree (`docs/`) compatible with `oi-lab/oi-laravel-documentation` (`doc:import`).
- Pest/Testbench test suite covering the catalog, usage reporter, prompt registry, registry command, and service provider wiring.
- Support for PHP 8.2+ and Laravel 12/13.
