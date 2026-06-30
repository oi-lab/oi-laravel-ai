<?php

namespace OiLab\OiLaravelAi\Contracts;

/**
 * Bridge to the host application's settings storage. The package never persists
 * settings itself; it delegates to the implementation the host binds through
 * `config('oi-laravel-ai.setting_store')`.
 */
interface SettingStore
{
    /**
     * Resolve a setting value, preferring the team-scoped value and falling back
     * to the global one.
     */
    public function get(string $key, ?string $teamId = null): mixed;

    /**
     * Create or update a setting.
     */
    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $teamId = null): void;

    /**
     * Remove a setting so reads fall back to the next level.
     */
    public function forget(string $key, ?string $teamId = null): void;
}
