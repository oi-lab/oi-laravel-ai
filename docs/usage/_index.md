---
title: Overview
description: The four building blocks of OI Laravel AI
section: usage
order: 1
---

# Usage

OI Laravel AI is made of four independent pieces you can adopt incrementally:

- [Catalog](catalog.md) — providers, models, and pricing, backed by a JSON registry.
- [Usage tracking](usage-tracking.md) — automatic recording of every agent call.
- [Cost reporting](cost-reporting.md) — aggregate usage and cost over a period.
- [Prompt registry](prompt-registry.md) — central agent system prompts with variable compilation.

Each piece is exposed through a small, stateless API in `OiLab\OiLaravelAi\Support` and `OiLab\OiLaravelAi\Services`. Models live in `OiLab\OiLaravelAi\Models`.
