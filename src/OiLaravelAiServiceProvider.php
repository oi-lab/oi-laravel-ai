<?php

namespace OiLab\OiLaravelAi;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\Events\AgentPrompted;
use OiLab\OiLaravelAi\Console\Commands\InstallAiSkillCommand;
use OiLab\OiLaravelAi\Console\Commands\UpdateAiRegistryCommand;
use OiLab\OiLaravelAi\Contracts\SettingStore;
use OiLab\OiLaravelAi\Listeners\AiRequestListener;
use OiLab\OiLaravelAi\Stores\OiLaravelSettingsStore;
use OiLab\OiLaravelSettings\SettingsManager;

class OiLaravelAiServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/oi-laravel-ai.php', 'oi-laravel-ai');

        $this->registerSettingStore();
    }

    /**
     * Bootstrap package services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Event::listen(AgentPrompted::class, AiRequestListener::class);

        if ($this->app->runningInConsole()) {
            $this->commands([
                UpdateAiRegistryCommand::class,
                InstallAiSkillCommand::class,
            ]);

            $this->registerPublishing();
        }
    }

    /**
     * Bind the host application's SettingStore implementation so the catalog
     * seeder can persist default model assignments and prompt overrides.
     */
    protected function registerSettingStore(): void
    {
        $this->app->bind(SettingStore::class, function ($app) {
            $implementation = config('oi-laravel-ai.setting_store');

            if ($implementation === null && class_exists(SettingsManager::class)) {
                $implementation = OiLaravelSettingsStore::class;
            }

            return $implementation ? $app->make($implementation) : null;
        });
    }

    /**
     * Register the publishable config and migrations so the host app can
     * customize them.
     */
    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__.'/../config/oi-laravel-ai.php' => config_path('oi-laravel-ai.php'),
        ], 'oi-laravel-ai-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'oi-laravel-ai-migrations');

        $this->publishes([
            __DIR__.'/../resources/stubs/ai-skill.md' => base_path('.claude/skills/oilab-laravel-ai/SKILL.md'),
        ], 'oi-laravel-ai-skill');
    }
}
