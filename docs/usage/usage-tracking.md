---
title: Usage tracking
description: Automatic recording of every agent call
section: usage
order: 3
---

# Usage tracking

The service provider registers `AiRequestListener` on `Laravel\Ai\Events\AgentPrompted`. Every agent prompt dispatched through `laravel/ai` is recorded into `ai_requests` automatically — you don't call anything.

## What gets recorded

For each prompt the listener stores:

- `prompt_type` — the agent class basename (e.g. `DocWriterAgent`).
- `prompt_system`, `prompt_input`, `response` — truncated to 10 000 characters.
- Token counts — `tokens_input`, `tokens_output`, `tokens_cache_write`, `tokens_cache_read`, `tokens_reasoning`.
- `ai_provider_id` / `ai_model_id` — mapped back to the catalog from the response's provider and model codes.
- `project_id` — read from the agent's `project` relation when available.

i> The listener is fully guarded. Any failure while tracing is caught and logged as a warning, so usage tracking can never break the agent call itself.

## Querying

`AiRequest` is a plain Eloquent model (it has no `updated_at`):

```php
use OiLab\OiLaravelAi\Models\AiRequest;

AiRequest::query()
    ->where('prompt_type', 'DocWriterAgent')
    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
    ->sum('tokens_output');
```

To turn raw requests into cost figures, use the [cost reporting](cost-reporting.md) service.
