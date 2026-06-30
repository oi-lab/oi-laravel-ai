<?php

namespace OiLab\OiLaravelAi\Data;

use OiLab\OiLaravelAi\Models\AiRequest;
use Spatie\LaravelData\Data;

class AiRequestData extends Data
{
    public function __construct(
        public readonly string $prompt_type,
        public readonly ?string $prompt_system = null,
        public readonly ?string $prompt_input = null,
        public readonly ?string $response = null,
        public readonly int $tokens_input = 0,
        public readonly int $tokens_output = 0,
        public readonly int $tokens_cache_write = 0,
        public readonly int $tokens_cache_read = 0,
        public readonly int $tokens_reasoning = 0,
        public readonly int $duration = 0,
        /** @var array<string, mixed>|null */
        public readonly ?array $prompt_schema = null,
        public readonly ?string $project_id = null,
        public readonly ?int $ai_provider_id = null,
        public readonly ?int $ai_model_id = null,
        public readonly ?string $agent_run_id = null,
    ) {}

    public static function fromModel(AiRequest $request): self
    {
        return new self(
            prompt_type: $request->prompt_type,
            prompt_system: $request->prompt_system,
            prompt_input: $request->prompt_input,
            response: $request->response,
            tokens_input: $request->tokens_input,
            tokens_output: $request->tokens_output,
            tokens_cache_write: $request->tokens_cache_write,
            tokens_cache_read: $request->tokens_cache_read,
            tokens_reasoning: $request->tokens_reasoning,
            duration: $request->duration,
            prompt_schema: $request->prompt_schema,
            project_id: $request->project_id,
            ai_provider_id: $request->ai_provider_id,
            ai_model_id: $request->ai_model_id,
            agent_run_id: $request->agent_run_id,
        );
    }
}
