<?php

namespace OiLab\OiLaravelAi\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use OiLab\OiLaravelAi\Support\AiCatalog;
use Throwable;

/**
 * Fetch a fresh provider/model catalog (with up-to-date pricing) from the
 * configured raw Git URL, upsert it into the database and persist the file to
 * the host-owned registry path for future offline seeds.
 */
class UpdateAiRegistryCommand extends Command
{
    protected $signature = 'ai:update-registry
        {--url= : Override the registry URL configured in oi-laravel-ai.registry.url}
        {--no-write : Upsert the catalog without persisting the downloaded file}';

    protected $description = 'Update the AI provider/model catalog and pricing from the remote registry';

    public function handle(): int
    {
        $url = $this->option('url') ?: config('oi-laravel-ai.registry.url');

        if (! $url) {
            $this->components->error('No registry URL configured. Set OI_AI_REGISTRY_URL or pass --url.');

            return self::FAILURE;
        }

        $this->components->info("Fetching AI registry from {$url}");

        try {
            $response = Http::acceptJson()->get($url);
        } catch (Throwable $e) {
            $this->components->error("Failed to fetch registry: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! $response->successful()) {
            $this->components->error("Registry request failed with status {$response->status()}.");

            return self::FAILURE;
        }

        try {
            $registry = AiCatalog::decode($response->body());
        } catch (Throwable $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $counts = AiCatalog::sync($registry);

        $this->components->info("Upserted {$counts['providers']} providers and {$counts['models']} models.");

        if (! $this->option('no-write')) {
            $this->persist($response->body());
        }

        return self::SUCCESS;
    }

    private function persist(string $json): void
    {
        $path = AiCatalog::path();

        if (! is_writable(dirname($path))) {
            $this->components->warn("Registry path [{$path}] is not writable; skipped saving the file. Configure oi-laravel-ai.registry.path to a writable location.");

            return;
        }

        file_put_contents($path, $json);

        $this->components->info("Saved registry to {$path}.");
    }
}
