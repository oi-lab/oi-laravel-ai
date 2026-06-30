<?php

use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use OiLab\OiLaravelAi\Support\AiCatalog;

it('upserts providers and models from a registry', function () {
    $counts = AiCatalog::sync([
        'providers' => [
            ['code' => 'anthropic', 'name' => 'Anthropic', 'owner' => 'Anthropic PBC'],
        ],
        'models' => [
            ['code' => 'claude-sonnet', 'name' => 'Claude Sonnet', 'type' => 'text', 'cost_input' => 3.0, 'cost_output' => 15.0, 'provider' => 'anthropic'],
        ],
    ]);

    expect($counts)->toBe(['providers' => 1, 'models' => 1]);
    expect(AiProvider::count())->toBe(1);

    $model = AiModel::first();
    expect($model->code)->toBe('claude-sonnet')
        ->and($model->cost_output)->toBe(15.0)
        ->and($model->aiProvider->code)->toBe('anthropic');
});

it('is idempotent and updates pricing on re-sync', function () {
    $registry = [
        'providers' => [['code' => 'openai', 'name' => 'OpenAI']],
        'models' => [['code' => 'gpt', 'name' => 'GPT', 'cost_input' => 5.0, 'cost_output' => 15.0, 'provider' => 'openai']],
    ];

    AiCatalog::sync($registry);

    $registry['models'][0]['cost_output'] = 20.0;
    AiCatalog::sync($registry);

    expect(AiModel::count())->toBe(1)
        ->and(AiModel::first()->cost_output)->toBe(20.0);
});

it('skips models whose provider is unknown', function () {
    AiCatalog::sync([
        'providers' => [['code' => 'anthropic', 'name' => 'Anthropic']],
        'models' => [['code' => 'orphan', 'name' => 'Orphan', 'provider' => 'missing']],
    ]);

    expect(AiModel::count())->toBe(0);
});

it('syncs the bundled registry asset', function () {
    AiCatalog::sync(AiCatalog::read(AiCatalog::defaultPath()));

    expect(AiProvider::count())->toBeGreaterThan(0)
        ->and(AiModel::count())->toBeGreaterThan(0);
});
