<?php

use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;

$settingStoreOptions = [
    'auto' => 'Auto-detect oi-laravel-settings (recommended)',
    'custom' => 'A custom SettingStore class',
    'none' => 'Disable settings (catalog only)',
];

it('walks through the assisted installation and reports a summary', function () use ($settingStoreOptions) {
    $this->artisan('ai:install')
        ->expectsConfirmation('Publish the configuration file (config/oi-laravel-ai.php)?', 'no')
        ->expectsQuestion('Host Project model (ai_requests.project_id foreign key)', 'App\\Models\\Project')
        ->expectsQuestion('Host AgentRun model (ai_requests.agent_run_id foreign key)', 'App\\Models\\AgentRun')
        ->expectsChoice('How should the AI settings (default models, prompt overrides) be stored?', 'auto', $settingStoreOptions)
        ->expectsQuestion('Remote registry URL for the provider/model catalog & pricing', '')
        ->expectsConfirmation('Publish the migrations into your application?', 'no')
        ->expectsConfirmation('Run the database migrations now?', 'no')
        ->expectsConfirmation('Seed the provider/model catalog now?', 'no')
        ->expectsConfirmation('Install the AI assistant skill for coding agents?', 'no')
        ->assertSuccessful();
});

it('seeds the provider/model catalog when confirmed', function () use ($settingStoreOptions) {
    expect(AiProvider::count())->toBe(0);

    $this->artisan('ai:install')
        ->expectsConfirmation('Publish the configuration file (config/oi-laravel-ai.php)?', 'no')
        ->expectsQuestion('Host Project model (ai_requests.project_id foreign key)', 'App\\Models\\Project')
        ->expectsQuestion('Host AgentRun model (ai_requests.agent_run_id foreign key)', 'App\\Models\\AgentRun')
        ->expectsChoice('How should the AI settings (default models, prompt overrides) be stored?', 'auto', $settingStoreOptions)
        ->expectsQuestion('Remote registry URL for the provider/model catalog & pricing', '')
        ->expectsConfirmation('Publish the migrations into your application?', 'no')
        ->expectsConfirmation('Run the database migrations now?', 'no')
        ->expectsConfirmation('Seed the provider/model catalog now?', 'yes')
        ->expectsConfirmation('Install the AI assistant skill for coding agents?', 'no')
        ->assertSuccessful();

    expect(AiProvider::count())->toBeGreaterThan(0)
        ->and(AiModel::count())->toBeGreaterThan(0);
});

it('captures a custom setting store class', function () use ($settingStoreOptions) {
    $this->artisan('ai:install')
        ->expectsConfirmation('Publish the configuration file (config/oi-laravel-ai.php)?', 'no')
        ->expectsQuestion('Host Project model (ai_requests.project_id foreign key)', 'App\\Models\\Project')
        ->expectsQuestion('Host AgentRun model (ai_requests.agent_run_id foreign key)', 'App\\Models\\AgentRun')
        ->expectsChoice('How should the AI settings (default models, prompt overrides) be stored?', 'custom', $settingStoreOptions)
        ->expectsQuestion('SettingStore implementation class', 'App\\Ai\\MySettingStore')
        ->expectsQuestion('Remote registry URL for the provider/model catalog & pricing', '')
        ->expectsConfirmation('Publish the migrations into your application?', 'no')
        ->expectsConfirmation('Run the database migrations now?', 'no')
        ->expectsConfirmation('Seed the provider/model catalog now?', 'no')
        ->expectsConfirmation('Install the AI assistant skill for coding agents?', 'no')
        ->assertSuccessful();

    expect(config('oi-laravel-ai.setting_store'))->toBe('App\\Ai\\MySettingStore');
});
