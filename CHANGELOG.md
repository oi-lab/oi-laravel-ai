# Changelog

All notable changes to `oi-lab/oi-laravel-ai` will be documented in this file.

## [Unreleased]

## [1.2.0] - 2026-07-31

### Changed

- `laravel/ai` support moved from `^0.7|^0.8` to `^0.8|^0.9|^0.10`. The SDK surface the usage listener reads (`AgentPrompted`, `AgentPrompt`, `AgentResponse`, `Usage`, `Meta`) is unchanged across those releases, so no application code has to change.
- `php` requirement raised from `^8.2` to `^8.3`. This is a correction rather than a dropped platform: `laravel/ai` has required PHP 8.3 since 0.6, so PHP 8.2 was never actually installable.
- `laravel/prompts` narrowed to `^0.3.6`, the range `laravel/ai` itself requires (`^0.1.24` was unreachable).
- Dev dependencies moved to Pest 5 (`^4.0|^5.0`) and Pint `^1.30`; the PHPUnit config now references the 13.2 schema.
- Documentation: the "How it fits together" architecture diagram in `docs/getting-started/_index.md` is now authored as a Mermaid `flowchart` block instead of ASCII art, matching the Mermaid diagram standard rendered by `oi-lab/oi-laravel-documentation`.

### Added

- CI workflow running the suite against PHP 8.3/8.4 and Laravel 12/13.
- Feature coverage for `AiRequestListener`: tests now dispatch a real `AgentPrompted` event built from Laravel AI SDK objects and assert the persisted `AiRequest`, so an incompatible SDK change fails the suite instead of being swallowed by the listener's `try/catch`.

### Fixed

- The test environment no longer misses `Spatie\LaravelData\LaravelDataServiceProvider`, which made `AiRequestData::toArray()` throw (and the listener silently log-and-drop the request) under Testbench.

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
