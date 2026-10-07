<?php

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Events\AgentPrompted;
use Laravel\Ai\Events\PromptingAgent;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\Usage;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelAi\Tests\Fixtures\RedactedAgent;
use OiLab\OiLaravelAi\Tests\Fixtures\TrackedAgent;

/**
 * These tests dispatch a real AgentPrompted event built from the Laravel AI SDK
 * objects, so they break if the SDK changes the prompt, response, usage or meta
 * shape the listener reads from.
 */
function agentPromptedEvent(string $provider = 'openai', string $model = 'gpt-5', ?Agent $agent = null): AgentPrompted
{
    $prompt = new AgentPrompt(
        agent: $agent ?? new TrackedAgent,
        prompt: 'Summarize the release notes.',
        attachments: [],
        provider: Mockery::mock(TextProvider::class),
        model: $model,
    );

    $response = new AgentResponse(
        invocationId: 'invocation-1',
        text: 'Here is the summary.',
        usage: new Usage(
            promptTokens: 120,
            completionTokens: 45,
            cacheWriteInputTokens: 10,
            cacheReadInputTokens: 5,
            reasoningTokens: 30,
        ),
        meta: new Meta(provider: $provider, model: $model),
    );

    return new AgentPrompted('invocation-1', $prompt, $response);
}

it('records a request when an agent is prompted', function () {
    $aiProvider = AiProvider::create(['code' => 'openai', 'name' => 'OpenAI', 'owner' => 'OpenAI']);
    $aiModel = AiModel::create([
        'code' => 'gpt-5',
        'name' => 'GPT-5',
        'type' => 'text',
        'cost_input' => 1.25,
        'cost_output' => 10,
        'ai_provider_id' => $aiProvider->id,
    ]);

    event(agentPromptedEvent());

    $request = AiRequest::sole();

    expect($request->prompt_type)->toBe('TrackedAgent')
        ->and($request->prompt_system)->toBe('You are a helpful assistant.')
        ->and($request->prompt_input)->toBe('Summarize the release notes.')
        ->and($request->response)->toBe('Here is the summary.')
        ->and($request->tokens_input)->toBe(120)
        ->and($request->tokens_output)->toBe(45)
        ->and($request->tokens_cache_write)->toBe(10)
        ->and($request->tokens_cache_read)->toBe(5)
        ->and($request->tokens_reasoning)->toBe(30)
        ->and($request->ai_provider_id)->toBe($aiProvider->id)
        ->and($request->ai_model_id)->toBe($aiModel->id);
});

it('records a request even when the provider is unknown to the catalog', function () {
    event(agentPromptedEvent(provider: 'unlisted'));

    $request = AiRequest::sole();

    expect($request->ai_provider_id)->toBeNull()
        ->and($request->ai_model_id)->toBeNull()
        ->and($request->tokens_input)->toBe(120);
});

it('redacts the payloads of a listed prompt type but keeps tokens and model', function () {
    config()->set('oi-laravel-ai.redacted_prompt_types', ['crm.mail']);

    event(agentPromptedEvent(agent: new RedactedAgent));

    $request = AiRequest::sole();

    expect($request->prompt_type)->toBe('crm.mail')
        ->and($request->prompt_system)->toBe('sha256:'.hash('sha256', 'You write a prospecting email.').' (30 car.)')
        ->and($request->prompt_input)->toBe('sha256:'.hash('sha256', 'Summarize the release notes.').' (28 car.)')
        ->and($request->response)->toBe('sha256:'.hash('sha256', 'Here is the summary.').' (20 car.)')
        ->and($request->tokens_input)->toBe(120)
        ->and($request->tokens_output)->toBe(45);
});

it('keeps the payloads of an unlisted prompt type', function () {
    config()->set('oi-laravel-ai.redacted_prompt_types', ['crm.mail']);

    event(agentPromptedEvent());

    expect(AiRequest::sole()->response)->toBe('Here is the summary.');
});

it('redacts every payload when store_payloads is false', function () {
    config()->set('oi-laravel-ai.store_payloads', false);

    event(agentPromptedEvent());

    $request = AiRequest::sole();

    expect($request->prompt_system)->toStartWith('sha256:')
        ->and($request->prompt_input)->toStartWith('sha256:')
        ->and($request->response)->toStartWith('sha256:')
        ->and($request->tokens_input)->toBe(120);
});

it('measures the real duration between the prompting and prompted events', function () {
    $prompted = agentPromptedEvent();

    event(new PromptingAgent($prompted->invocationId, $prompted->prompt));
    usleep(30_000);
    event($prompted);

    expect(AiRequest::sole()->duration)->toBeGreaterThanOrEqual(30);
});

it('records a zero duration when the start of the invocation was not seen', function () {
    event(agentPromptedEvent());

    expect(AiRequest::sole()->duration)->toBe(0);
});
