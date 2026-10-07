<?php

namespace OiLab\OiLaravelAi\Listeners;

use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Events\StreamingAgent;
use OiLab\OiLaravelAi\Contracts\HasPromptType;
use OiLab\OiLaravelAi\Data\AiRequestData;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelAi\Support\InvocationTimer;
use OiLab\OiLaravelAi\Support\PayloadRedactor;
use Throwable;

class AiRequestListener
{
    public function __construct(
        private readonly PayloadRedactor $redactor,
        private readonly InvocationTimer $timer,
    ) {}

    /**
     * Remember when a (non-streamed) invocation started so its duration can be
     * recorded once it completes.
     */
    public function handlePrompting(PromptingAgent $event): void
    {
        if (! $event instanceof StreamingAgent) {
            $this->timer->start($event->invocationId);
        }
    }

    public function handle(AgentPrompted $event): void
    {
        try {
            $providerCode = $event->response->meta->provider;
            $modelCode = $event->response->meta->model ?? $event->prompt->model;

            $aiProvider = $providerCode ? AiProvider::where('code', $providerCode)->first() : null;
            $aiModel = ($aiProvider && $modelCode)
                ? AiModel::where('code', $modelCode)->where('ai_provider_id', $aiProvider->id)->first()
                : null;

            $agent = $event->prompt->agent;
            $projectId = null;

            try {
                $projectId = $agent->project?->id;
            } catch (Throwable) {
                // Agent may not expose project publicly (e.g. SectionUpdaterAgent)
            }

            $promptType = $agent instanceof HasPromptType ? $agent->promptType() : class_basename($agent);

            $data = new AiRequestData(
                prompt_type: $promptType,
                prompt_system: $this->redactor->payload($agent->instructions(), $promptType),
                prompt_input: $this->redactor->payload($event->prompt->prompt, $promptType),
                response: $this->redactor->payload($event->response->text, $promptType),
                tokens_input: $event->response->usage->promptTokens,
                tokens_output: $event->response->usage->completionTokens,
                tokens_cache_write: $event->response->usage->cacheWriteInputTokens,
                tokens_cache_read: $event->response->usage->cacheReadInputTokens,
                tokens_reasoning: $event->response->usage->reasoningTokens,
                duration: $this->timer->stop($event->invocationId),
                prompt_schema: null,
                project_id: $projectId,
                ai_provider_id: $aiProvider?->id,
                ai_model_id: $aiModel?->id,
                agent_run_id: null,
            );

            AiRequest::create($data->toArray());
        } catch (Throwable $e) {
            // Never let tracing break the main flow
            logger()->warning('AiRequestListener failed: '.$e->getMessage());
        }
    }
}
