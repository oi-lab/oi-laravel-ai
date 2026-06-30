---
title: Catalog
description: Providers, models and pricing
section: usage
order: 2
---

# Catalog

The catalog is two tables: `ai_providers` and `ai_models`. Pricing is stored on the model as `cost_input` and `cost_output`, both in USD per 1 million tokens.

## Reading and syncing

`AiCatalog` reads the JSON registry and upserts it into the database:

```php
use OiLab\OiLaravelAi\Support\AiCatalog;

$registry = AiCatalog::read();         // bundled asset, or the host-configured path
$counts = AiCatalog::sync($registry);  // ['providers' => 5, 'models' => 15]
```

`sync()` is idempotent — it `updateOrCreate`s providers and models by their `code`, so running it repeatedly only refreshes names and pricing.

## The registry file

The bundled registry (`assets/registry.json`) describes providers and models only:

```json
{
    "providers": [
        { "code": "anthropic", "name": "Anthropic", "owner": "Anthropic PBC" }
    ],
    "models": [
        { "code": "claude-sonnet-4-6", "name": "Claude Sonnet 4.6", "type": "text", "cost_input": 3.0, "cost_output": 15.0, "provider": "anthropic" }
    ]
}
```

Both `providers` and `models` are upserted into the database by `sync()`. The registry is a generic catalog — it carries no application-specific concerns such as which model a given agent should use.

## Models

```php
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Models\AiModel;

$provider = AiProvider::where('code', 'anthropic')->first();
$provider->aiModels;          // HasMany<AiModel>

$model = AiModel::where('code', 'claude-sonnet-4-6')->first();
$model->aiProvider;           // BelongsTo<AiProvider>
$model->cost_output;          // 15.0 (USD / 1M tokens)
```

See [Catalog updates](../advanced/host-integration.md) for refreshing pricing from a remote URL.
