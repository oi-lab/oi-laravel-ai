<?php

namespace OiLab\OiLaravelAi\Tests;

use OiLab\OiLaravelAi\OiLaravelAiServiceProvider;
use OiLab\OiLaravelAi\Tests\Fixtures\AgentRun;
use OiLab\OiLaravelAi\Tests\Fixtures\Project;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelData\LaravelDataServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            OiLaravelAiServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        $app['config']->set('oi-laravel-ai.setting_store', null);
        $app['config']->set('oi-laravel-ai.models.project', Project::class);
        $app['config']->set('oi-laravel-ai.models.agent_run', AgentRun::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Host stub tables (projects, agent_runs) must exist before the package
        // migrations run, because ai_requests declares foreign keys to them.
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }
}
