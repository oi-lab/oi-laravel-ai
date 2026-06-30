---
title: Cost reporting
description: Aggregate usage and cost with AiUsageReporter
section: usage
order: 4
---

# Cost reporting

`AiUsageReporter` turns recorded requests into cost figures. Cost is derived from each request's token counts and its model's per-1M-token pricing.

## Per-project summary

```php
use OiLab\OiLaravelAi\Services\AiUsageReporter;

$summary = app(AiUsageReporter::class)->summaryForProjects(
    projectIds: [$project->id],
    start: now()->startOfMonth(),   // optional, defaults to start of current month
    end: now()->endOfMonth(),       // optional, defaults to end of current month
);
```

The returned `AiUsageSummaryData` exposes:

| Property | Type | Description |
|----------|------|-------------|
| `total_requests` | `int` | Number of requests in the period. |
| `total_tokens_input` | `int` | Summed input tokens. |
| `total_tokens_output` | `int` | Summed output tokens. |
| `estimated_cost_usd` | `float` | Total cost, rounded to 4 decimals. |
| `by_agent_type` | `array` | Per `prompt_type`: count and token totals. |
| `by_model` | `array` | Per model: name, count and token totals. |

## Cost of a single request

```php
$reporter = app(AiUsageReporter::class);

$cost = $reporter->costForRequest($request);
// (tokens_input / 1M * cost_input) + (tokens_output / 1M * cost_output)
```

A request with no associated model costs `0`.

## Cost of a collection

```php
$requests = AiRequest::with('aiModel')->whereIn('project_id', $ids)->get();

$total = $reporter->estimatedCost($requests);
```

x> Always eager-load `aiModel` (and `aiModel.aiProvider` for the model breakdown) to avoid N+1 queries — `summaryForProjects()` already does this for you.
