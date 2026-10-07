<?php

namespace OiLab\OiLaravelAi\Contracts;

/**
 * Lets an agent declare the prompt type recorded in `ai_requests` (and matched
 * against `redacted_prompt_types`) instead of its class basename.
 */
interface HasPromptType
{
    public function promptType(): string;
}
