# OI Laravel AI

Use the `oi-laravel-ai` package as the AI backend in this Laravel application: a provider/model
catalog with per-1M-token pricing (`AiProvider`/`AiModel`), automatic usage tracking of every
`Laravel\Ai\Events\AgentPrompted` event into `ai_requests`, cost reporting via `AiUsageReporter`,
and the agent system-prompt registry `AiPromptVariableRegistry`. The catalog seeds from
`assets/registry.json` and refreshes with `php artisan ai:update-registry`.

- IMPORTANT: Activate `oilab-laravel-ai` when tracking AI usage or cost, reading or compiling agent
  system prompts, working with the provider/model catalog and pricing, or wiring the host's
  `Project`/`AgentRun` models and `SettingStore` to this package. Resolve host models through
  `config('oi-laravel-ai.models.*')` — never hardcode the class.
