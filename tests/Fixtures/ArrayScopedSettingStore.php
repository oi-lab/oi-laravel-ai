<?php

namespace OiLab\OiLaravelAi\Tests\Fixtures;

use OiLab\OiLaravelAi\Contracts\ScopedSettingStore;

class ArrayScopedSettingStore implements ScopedSettingStore
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public array $layers = [];

    public function get(string $key, ?string $teamId = null): mixed
    {
        return $this->layers[$teamId ?? '*'][$key] ?? ($teamId === null ? null : $this->get($key));
    }

    public function own(string $key, ?string $teamId = null): mixed
    {
        return $this->layers[$teamId ?? '*'][$key] ?? null;
    }

    public function set(string $key, mixed $value, string $label, string $type = 'string', ?string $teamId = null): void
    {
        $this->layers[$teamId ?? '*'][$key] = $value;
    }

    public function forget(string $key, ?string $teamId = null): void
    {
        unset($this->layers[$teamId ?? '*'][$key]);
    }
}
