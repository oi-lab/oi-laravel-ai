<?php

namespace OiLab\OiLaravelAi\Listeners;

use Illuminate\Support\Str;
use Laravel\Ai\Events\AgentPrompted;
use OiLab\OiLaravelAi\Data\AiRequestData;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Models\AiRequest;
use Throwable;

class AiRequestListener
{
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

            $data = new AiRequestData(
                prompt_type: class_basename($agent),
                prompt_system: Str::limit($agent->instructions(), 10000),
                prompt_input: Str::limit($event->prompt->prompt, 10000),
                response: Str::limit($event->response->text, 10000),
                tokens_input: $event->response->usage->promptTokens,
                tokens_output: $event->response->usage->completionTokens,
                tokens_cache_write: $event->response->usage->cacheWriteInputTokens,
                tokens_cache_read: $event->response->usage->cacheReadInputTokens,
                tokens_reasoning: $event->response->usage->reasoningTokens,
                duration: 0,
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
