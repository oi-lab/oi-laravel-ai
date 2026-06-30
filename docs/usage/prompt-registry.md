---
title: Prompt registry
description: Central agent system prompts with variable compilation
section: usage
order: 5
---

# Prompt registry

`AiPromptVariableRegistry` is the single source of truth for your agents' system prompts. Each prompt has a human label, a template using `:variable` placeholders, and a list of the variables it accepts. The design mirrors the application's mail-template variable system.

## Listing prompts

```php
use OiLab\OiLaravelAi\Support\AiPromptVariableRegistry;

AiPromptVariableRegistry::keys();              // ['COMMIT_ANALYZER', 'DOC_WRITER', ...]
AiPromptVariableRegistry::label(AiPromptVariableRegistry::DOC_WRITER);
AiPromptVariableRegistry::defaultPrompt(AiPromptVariableRegistry::DOC_WRITER);
AiPromptVariableRegistry::variablesFor(AiPromptVariableRegistry::DOC_WRITER);
```

Every agent is also exposed as a class constant: `COMMIT_ANALYZER`, `PROJECT_PROFILER`, `LANGUAGE_DETECTOR`, `SECTION_SELECTOR`, `SECTION_EXPANDER`, `STRUCTURE_COHERENCE`, `DOC_RESEARCH`, `DOC_WRITER`, `DOC_REVIEWER`, `DOC_METADATA`, `SECTION_UPDATER`, `QUALITY_CHECKER`, `GLOSSARY_BUILDER`.

## Compiling a prompt

`compile()` replaces every `:variable` placeholder with its value:

```php
$prompt = AiPromptVariableRegistry::compile(
    AiPromptVariableRegistry::defaultPrompt(AiPromptVariableRegistry::DOC_WRITER),
    [
        'project_name' => 'Acme',
        'language' => 'français',
        'section_title' => 'Vue d\'ensemble',
    ],
);
```

## Globals

Some variables are available to every prompt and injected automatically at compile time. `app_name` (from `config('app.name')`) is always merged in, so you never pass it explicitly.

```php
AiPromptVariableRegistry::globals(); // [AiPromptVariableData('app_name', ...)]
```

## Building a settings UI

`variablesFor()` returns `AiPromptVariableData` objects (`name`, `description`, `example`) — ideal for rendering an editable form where users override a prompt and see which placeholders they may use. Persist overrides through your [`SettingStore`](../advanced/host-integration.md); the `AiCatalogSeeder` seeds the defaults for you.
