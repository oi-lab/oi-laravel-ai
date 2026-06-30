<?php

namespace OiLab\OiLaravelAi\Support;

use InvalidArgumentException;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;

/**
 * Loads the provider/model catalog (and pricing) from the JSON registry and
 * upserts it into the database. Shared by the catalog seeder and the
 * `ai:update-registry` command so both read and persist the catalog identically.
 */
final class AiCatalog
{
    /**
     * Absolute path of the registry file shipped with the package.
     */
    public static function defaultPath(): string
    {
        return __DIR__.'/../../assets/registry.json';
    }

    /**
     * Resolve the active registry path: the host-configured copy if any,
     * otherwise the bundled asset.
     */
    public static function path(): string
    {
        return config('oi-laravel-ai.registry.path') ?: self::defaultPath();
    }

    /**
     * Read and decode a registry file.
     *
     * @return array{providers: array<int, array<string, mixed>>, models: array<int, array<string, mixed>>}
     */
    public static function read(?string $path = null): array
    {
        $path ??= self::path();

        if (! is_file($path)) {
            throw new InvalidArgumentException("AI registry file not found at [{$path}].");
        }

        return self::decode((string) file_get_contents($path));
    }

    /**
     * Decode and validate raw registry JSON.
     *
     * @return array{providers: array<int, array<string, mixed>>, models: array<int, array<string, mixed>>}
     */
    public static function decode(string $json): array
    {
        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['providers'], $data['models'])) {
            throw new InvalidArgumentException('Invalid AI registry payload: expected "providers" and "models" keys.');
        }

        return [
            'providers' => $data['providers'],
            'models' => $data['models'],
        ];
    }

    /**
     * Upsert providers and models (codes, names, types and pricing) into the
     * database. Returns the number of providers and models written.
     *
     * @param  array{providers: array<int, array<string, mixed>>, models: array<int, array<string, mixed>>}  $registry
     * @return array{providers: int, models: int}
     */
    public static function sync(array $registry): array
    {
        $providerIdsByCode = [];

        foreach ($registry['providers'] as $provider) {
            $model = AiProvider::updateOrCreate(
                ['code' => $provider['code']],
                ['name' => $provider['name'], 'owner' => $provider['owner'] ?? null],
            );

            $providerIdsByCode[$provider['code']] = $model->id;
        }

        foreach ($registry['models'] as $model) {
            $providerId = $providerIdsByCode[$model['provider']] ?? null;

            if ($providerId === null) {
                continue;
            }

            AiModel::updateOrCreate(
                ['code' => $model['code']],
                [
                    'name' => $model['name'],
                    'type' => $model['type'] ?? 'text',
                    'cost_input' => $model['cost_input'] ?? 0,
                    'cost_output' => $model['cost_output'] ?? 0,
                    'ai_provider_id' => $providerId,
                ],
            );
        }

        return [
            'providers' => count($registry['providers']),
            'models' => count($registry['models']),
        ];
    }
}
