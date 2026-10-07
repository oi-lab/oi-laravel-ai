<?php

namespace OiLab\OiLaravelAi\Support;

use Illuminate\Support\Str;

/**
 * Decides whether the payloads (system prompt, input, response) of a prompt type
 * may be stored, and replaces them with a fingerprint when they may not.
 */
class PayloadRedactor
{
    public function shouldRedact(string $promptType): bool
    {
        if (! config('oi-laravel-ai.store_payloads', true)) {
            return true;
        }

        return Str::is((array) config('oi-laravel-ai.redacted_prompt_types', []), $promptType);
    }

    /**
     * Return the text as stored: kept (truncated to $limit) or redacted.
     */
    public function payload(?string $text, string $promptType, int $limit = 10000): ?string
    {
        if ($text === null) {
            return null;
        }

        if ($this->shouldRedact($promptType)) {
            return 'sha256:'.hash('sha256', $text).' ('.mb_strlen($text).' car.)';
        }

        return Str::limit($text, $limit);
    }
}
