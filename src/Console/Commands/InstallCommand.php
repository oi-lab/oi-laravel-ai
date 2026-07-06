<?php

namespace OiLab\OiLaravelAi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use OiLab\OiLaravelAi\Database\Seeders\AiCatalogSeeder;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Assisted, interactive installation for oi-laravel-ai: publishes the config,
 * captures the host models / setting store / registry into .env, publishes and
 * runs the migrations, and seeds the provider/model catalog — each step gated by
 * a confirmation so the command is safe to re-run.
 */
class InstallCommand extends Command
{
    protected $signature = 'ai:install {--force : Overwrite already published files}';

    protected $description = 'Assisted installation: publish, configure, migrate and seed the AI catalog';

    /**
     * The env values collected during the wizard, written out at the end.
     *
     * @var array<string, string>
     */
    private array $env = [];

    /**
     * Human-readable summary rows shown at the end.
     *
     * @var list<array{0: string, 1: string}>
     */
    private array $summary = [];

    public function handle(): int
    {
        intro('OI Laravel AI — assisted installation');

        $this->publishConfig();
        $this->configureModels();
        $this->configureSettingStore();
        $this->configureRegistry();
        $this->publishMigrations();
        $this->runMigrations();
        $this->seedCatalog();
        $this->installSkill();

        $this->writeEnv();

        if ($this->summary !== []) {
            table(['Step', 'Result'], $this->summary);
        }

        outro('oi-laravel-ai is installed. Review config/oi-laravel-ai.php and the values written to .env.');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        if (! confirm('Publish the configuration file (config/oi-laravel-ai.php)?', default: true)) {
            $this->summary[] = ['Config', 'skipped'];

            return;
        }

        $this->callSilently('vendor:publish', [
            '--tag' => 'oi-laravel-ai-config',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->summary[] = ['Config', 'published'];
    }

    private function configureModels(): void
    {
        $project = text(
            label: 'Host Project model (ai_requests.project_id foreign key)',
            placeholder: 'App\\Models\\Project',
            default: (string) config('oi-laravel-ai.models.project', 'App\\Models\\Project'),
            hint: 'The UUID-keyed model AI requests are scoped to. Leave the default if you have none.',
        );

        $agentRun = text(
            label: 'Host AgentRun model (ai_requests.agent_run_id foreign key)',
            placeholder: 'App\\Models\\AgentRun',
            default: (string) config('oi-laravel-ai.models.agent_run', 'App\\Models\\AgentRun'),
        );

        $this->env['OI_AI_PROJECT_MODEL'] = $project;
        $this->env['OI_AI_AGENT_RUN_MODEL'] = $agentRun;
        config()->set('oi-laravel-ai.models.project', $project);
        config()->set('oi-laravel-ai.models.agent_run', $agentRun);

        $this->summary[] = ['Project model', $project];
        $this->summary[] = ['AgentRun model', $agentRun];
    }

    private function configureSettingStore(): void
    {
        $choice = select(
            label: 'How should the AI settings (default models, prompt overrides) be stored?',
            options: [
                'auto' => 'Auto-detect oi-laravel-settings (recommended)',
                'custom' => 'A custom SettingStore class',
                'none' => 'Disable settings (catalog only)',
            ],
            default: 'auto',
        );

        $value = match ($choice) {
            'custom' => text(
                label: 'SettingStore implementation class',
                placeholder: 'App\\Ai\\MySettingStore',
                required: true,
            ),
            'none' => '',
            default => null,
        };

        if ($value !== null) {
            $this->env['OI_AI_SETTING_STORE'] = $value;
            config()->set('oi-laravel-ai.setting_store', $value);
        }

        $this->summary[] = ['Setting store', $choice === 'auto' ? 'auto-detect' : ($choice === 'none' ? 'disabled' : $value)];
    }

    private function configureRegistry(): void
    {
        $url = text(
            label: 'Remote registry URL for the provider/model catalog & pricing',
            placeholder: 'https://raw.githubusercontent.com/…/registry.json',
            default: (string) config('oi-laravel-ai.registry.url', ''),
            hint: 'Optional — used by `ai:update-registry`. Leave empty to rely on the bundled catalog.',
        );

        if ($url !== '') {
            $this->env['OI_AI_REGISTRY_URL'] = $url;
            config()->set('oi-laravel-ai.registry.url', $url);
            $this->summary[] = ['Registry URL', $url];
        }
    }

    private function publishMigrations(): void
    {
        if (! confirm('Publish the migrations into your application?', default: false)) {
            note('Migrations load automatically from the package, so publishing is only needed to customize them.');

            return;
        }

        $this->callSilently('vendor:publish', [
            '--tag' => 'oi-laravel-ai-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        $this->summary[] = ['Migrations', 'published'];
    }

    private function runMigrations(): void
    {
        $this->warnAboutMissingHostTables();

        if (! confirm('Run the database migrations now?', default: true)) {
            $this->summary[] = ['Migrations', 'not run'];

            return;
        }

        $this->call('migrate', ['--force' => true]);
        $this->summary[] = ['Migrations', 'run'];
    }

    private function warnAboutMissingHostTables(): void
    {
        $missing = array_values(array_filter(
            ['projects', 'agent_runs'],
            static fn (string $table): bool => ! Schema::hasTable($table),
        ));

        if ($missing !== []) {
            warning(
                'ai_requests foreign-keys to ['.implode(', ', $missing).'], which do not exist yet. '
                .'Create those tables (or publish and adjust the migration to drop those foreign keys) before migrating.'
            );
        }
    }

    private function seedCatalog(): void
    {
        if (! confirm('Seed the provider/model catalog now?', default: true)) {
            $this->summary[] = ['Catalog', 'not seeded'];

            return;
        }

        try {
            spin(
                fn () => $this->getLaravel()->make(AiCatalogSeeder::class)->run(),
                'Seeding providers, models and default prompts…',
            );
        } catch (Throwable $e) {
            warning('Catalog seeding failed: '.$e->getMessage());
            $this->summary[] = ['Catalog', 'failed'];

            return;
        }

        $this->summary[] = ['Catalog', AiProvider::query()->count().' providers, '.AiModel::query()->count().' models'];
    }

    private function installSkill(): void
    {
        if (! confirm('Install the AI assistant skill for coding agents?', default: false)) {
            return;
        }

        if ($this->getApplication()->has('oi:skills')) {
            $this->call('oi:skills', ['skills' => ['oilab-laravel-ai'], '--project' => true]);
        } elseif ($this->getApplication()->has('oi-ai:install-ai-skill')) {
            $this->call('oi-ai:install-ai-skill');
        } else {
            note('Skill installer not available. Install oi-lab/oi-laravel-development and run `php artisan oi:skills`.');

            return;
        }

        $this->summary[] = ['AI skill', 'installed'];
    }

    private function writeEnv(): void
    {
        if ($this->env === []) {
            return;
        }

        $path = $this->getLaravel()->environmentFilePath();

        if (! is_file($path)) {
            note('No .env file found; add these manually: '.implode(' ', array_map(
                static fn (string $key, string $value): string => $key.'='.$value,
                array_keys($this->env),
                array_values($this->env),
            )));

            return;
        }

        $content = (string) file_get_contents($path);

        foreach ($this->env as $key => $value) {
            $line = $key.'='.$this->escapeEnvValue($value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            $content = preg_match($pattern, $content)
                ? (string) preg_replace($pattern, $line, $content)
                : rtrim($content, "\n")."\n".$line."\n";
        }

        file_put_contents($path, $content);
        note('Updated .env with '.implode(', ', array_keys($this->env)).'.');
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return preg_match('/\s|\\\\|"|#/', $value) ? '"'.str_replace('"', '\"', $value).'"' : $value;
    }
}
