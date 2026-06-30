<?php

use Illuminate\Support\Facades\Http;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;

it('fetches the registry, upserts the catalog and reports counts', function () {
    Http::fake([
        'https://example.test/registry.json' => Http::response([
            'providers' => [['code' => 'anthropic', 'name' => 'Anthropic']],
            'models' => [['code' => 'claude', 'name' => 'Claude', 'cost_input' => 3.0, 'cost_output' => 15.0, 'provider' => 'anthropic']],
        ]),
    ]);

    config()->set('oi-laravel-ai.registry.url', 'https://example.test/registry.json');

    $this->artisan('ai:update-registry', ['--no-write' => true])
        ->assertSuccessful();

    expect(AiProvider::count())->toBe(1)
        ->and(AiModel::where('code', 'claude')->exists())->toBeTrue();
});

it('respects the --url option over the configured url', function () {
    Http::fake([
        'https://override.test/*' => Http::response([
            'providers' => [['code' => 'openai', 'name' => 'OpenAI']],
            'models' => [],
        ]),
    ]);

    config()->set('oi-laravel-ai.registry.url', 'https://configured.test/registry.json');

    $this->artisan('ai:update-registry', ['--url' => 'https://override.test/registry.json', '--no-write' => true])
        ->assertSuccessful();

    expect(AiProvider::where('code', 'openai')->exists())->toBeTrue();
});

it('fails when no registry url is configured', function () {
    config()->set('oi-laravel-ai.registry.url', null);

    $this->artisan('ai:update-registry', ['--no-write' => true])
        ->assertFailed();
});

it('fails when the registry request is not successful', function () {
    Http::fake([
        'https://example.test/*' => Http::response('nope', 500),
    ]);

    config()->set('oi-laravel-ai.registry.url', 'https://example.test/registry.json');

    $this->artisan('ai:update-registry', ['--no-write' => true])
        ->assertFailed();
});
