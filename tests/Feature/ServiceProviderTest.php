<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Events\AgentPrompted;
use OiLab\OiLaravelAi\Contracts\SettingStore;

it('loads the package configuration', function () {
    expect(config('oi-laravel-ai'))->toBeArray()
        ->and(config('oi-laravel-ai.registry'))->toBeArray();
});

it('registers the AgentPrompted usage listener', function () {
    expect(Event::hasListeners(AgentPrompted::class))->toBeTrue();
});

it('registers the package artisan commands', function () {
    $commands = array_keys($this->app[Kernel::class]->all());

    expect($commands)
        ->toContain('ai:update-registry')
        ->toContain('oi-ai:install-ai-skill');
});

it('does not resolve a setting store when none is configured', function () {
    config()->set('oi-laravel-ai.setting_store', null);

    expect(app(SettingStore::class))->toBeNull();
});
