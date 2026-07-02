<?php

namespace OiLab\OiLaravelAi\Stores;

use OiLab\OiLaravelAi\Contracts\SettingStore;
use OiLab\OiLaravelSettings\SettingsManager;

/**
 * First-class {@see SettingStore} adapter backed by `oi-lab/oi-laravel-settings`.
 *
 * Wired automatically when that package is installed, so AI prompt overrides and
 * default model assignments are persisted in the shared, scoped, typed Setting
 * store. The AI team id maps directly onto a settings scope (null = global).
 */
class OiLaravelSettingsStore implements SettingStore
{
    public function __construct(protected SettingsManager $settings) {}

    public function get(string $key, ?string $teamId = null): mixed
    {
        return $this->settings->get($key, scope: $teamId);
    }

    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $teamId = null): void
    {
        $this->settings->set($key, $value, type: $type, label: $label, scope: $teamId);
    }

    public function forget(string $key, ?string $teamId = null): void
    {
        $this->settings->delete($key, scope: $teamId);
    }
}
