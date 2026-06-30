---
title: AI Assistant Skills
description: Give AI coding assistants context about this package
section: advanced
order: 4
---

# AI Assistant Skills

When working with an AI coding assistant (Claude Code, JetBrains AI, etc.) in a Laravel project that uses oi-laravel-ai, the AI benefits from knowing how the package is wired: that usage is tracked automatically, that host models are resolved through config, and that prompts live in a central registry.

The package ships a skill file that communicates this context automatically.

## Installing the skill

The recommended way is the unified `oi:skills` command (provided by `oi-lab/oi-laravel-development`):

```bash
php artisan oi:skills
```

It discovers the skills declared by every installed `oi-lab/*` package and lets you pick which ones to install, choosing whether to install them in the project (`.claude` + `.junie`) or your Claude Code user profile (`~/.claude`).

To install only this package's skill non-interactively:

```bash
php artisan oi:skills oilab-laravel-ai --project
# or, into your Claude Code user profile:
php artisan oi:skills oilab-laravel-ai --global
```

This command:

- Copies the skill to `.claude/skills/oilab-laravel-ai/` (Claude Code)
- Copies the skill to `.junie/skills/oilab-laravel-ai/` (JetBrains AI)
- Adds (or refreshes) the `=== oi-lab/oi-laravel-ai rules ===` section in your `CLAUDE.md`

> A package-local command `php artisan oi-ai:install-ai-skill` is still available for projects that don't use `oi-lab/oi-laravel-development`, but it is **deprecated** in favor of `oi:skills`.

## Keeping the skill up to date

When you update the package, re-run the command to pull in the latest skill content:

```bash
php artisan oi:skills oilab-laravel-ai --project
```

## What the skill tells the AI

- Usage tracking is automatic via the `AgentPrompted` listener — don't write recording code by hand.
- Resolve host models through `config('oi-laravel-ai.models.*')`, never hardcode the class.
- Compile prompts with `AiPromptVariableRegistry::compile()`; the `app_name` global is injected for you.
- Refresh pricing with `php artisan ai:update-registry`.

## Manual publishing

If you only need the skill file without touching `CLAUDE.md`:

```bash
php artisan vendor:publish --tag=oi-laravel-ai-skill
```

This copies the skill to `.claude/skills/oilab-laravel-ai/SKILL.md`.
