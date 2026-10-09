<?php

namespace OiLab\OiLaravelAi\Services;

use Illuminate\Contracts\Container\Container;
use LogicException;
use OiLab\OiLaravelAi\Contracts\ScopedSettingStore;
use OiLab\OiLaravelAi\Contracts\SettingStore;
use OiLab\OiLaravelAi\Data\PromptDefinition;
use OiLab\OiLaravelAi\Enums\PromptSource;
use OiLab\OiLaravelAi\Exceptions\UnknownPrompt;
use OiLab\OiLaravelAi\Support\PromptRegistry;

/**
 * Resolves, renders and edits the prompts of the registry.
 *
 * The template of a prompt is read layer by layer: the team's own override,
 * then the global override, then the definition's default. Overrides are
 * string settings stored through the host's {@see SettingStore}, under the
 * configured prefix followed by the prompt key.
 *
 * Every method takes the team explicitly (null = global): an agent usually
 * runs in a queued job, where no "current team" exists.
 *
 * Substitution is a single `strtr` pass: a value containing `:something` is
 * never expanded again, and the longest placeholder wins.
 */
class PromptManager
{
    public function __construct(
        protected PromptRegistry $registry,
        protected Container $container,
    ) {}

    public function registry(): PromptRegistry
    {
        return $this->registry;
    }

    /**
     * The setting key the overrides of a prompt are stored under.
     */
    public function settingKey(string $key): string
    {
        return config('oi-laravel-ai.prompts.setting_prefix', 'PROMPTS.').$key;
    }

    /**
     * The raw template of a prompt for a team, placeholders left untouched.
     *
     * @throws UnknownPrompt
     */
    public function template(string $key, string|int|null $teamId = null): string
    {
        $definition = $this->registry->get($key);

        foreach ($this->layers($definition, $teamId) as $layer) {
            $stored = $this->read($key, $layer);

            if ($stored !== null) {
                return $stored;
            }
        }

        return $definition->default;
    }

    /**
     * Where the template of a prompt comes from for a team.
     *
     * @throws UnknownPrompt
     */
    public function source(string $key, string|int|null $teamId = null): PromptSource
    {
        $definition = $this->registry->get($key);

        foreach ($this->layers($definition, $teamId) as $layer) {
            if ($this->read($key, $layer) !== null) {
                return $layer === null ? PromptSource::Global : PromptSource::Team;
            }
        }

        return PromptSource::Default;
    }

    /**
     * The prompt for a team with every placeholder replaced: the global
     * variables first, then the given ones (which win).
     *
     * @param  array<string, string|int|float|null>  $variables
     *
     * @throws UnknownPrompt
     */
    public function render(string $key, array $variables = [], string|int|null $teamId = null): string
    {
        return $this->compile($this->template($key, $teamId), $variables);
    }

    /**
     * The prompt rendered with the example value of each of its variables.
     *
     * @throws UnknownPrompt
     */
    public function preview(string $key, string|int|null $teamId = null, ?string $template = null): string
    {
        $examples = [];

        foreach ($this->registry->get($key)->variables as $variable) {
            $examples[$variable->name] = $variable->example ?? ':'.$variable->name;
        }

        return $this->compile($template ?? $this->template($key, $teamId), $examples);
    }

    /**
     * A short hash of the template in use, to trace which wording produced a
     * stored result once the template has been edited.
     *
     * @throws UnknownPrompt
     */
    public function fingerprint(string $key, string|int|null $teamId = null): string
    {
        return substr(hash('sha256', $this->template($key, $teamId)), 0, 8);
    }

    /**
     * Store the override of a prompt for a team (null = global).
     *
     * @throws UnknownPrompt
     */
    public function save(string $key, string $template, string|int|null $teamId = null): void
    {
        $definition = $this->assertWritable($key, $teamId);

        $this->store()->set($this->settingKey($key), $template, $definition->label, 'string', $this->team($teamId));
    }

    /**
     * Remove the override of a prompt for a team, so the next layer applies.
     *
     * @throws UnknownPrompt
     */
    public function reset(string $key, string|int|null $teamId = null): void
    {
        $this->assertWritable($key, $teamId);

        $this->store()->forget($this->settingKey($key), $this->team($teamId));
    }

    /**
     * Replace the `:variable` placeholders of a template.
     *
     * @param  array<string, string|int|float|null>  $variables
     */
    public function compile(string $template, array $variables = []): string
    {
        $replace = [];

        foreach ([...$this->registry->globalValues(), ...$variables] as $name => $value) {
            $replace[':'.$name] = (string) $value;
        }

        return strtr($template, $replace);
    }

    /**
     * The teams to read, most specific first.
     *
     * @return array<int, string|null>
     */
    protected function layers(PromptDefinition $definition, string|int|null $teamId): array
    {
        return $teamId === null || ! $definition->teamOverridable ? [null] : [$this->team($teamId), null];
    }

    /**
     * The non-empty override owned by one layer, or null.
     */
    protected function read(string $key, ?string $teamId): ?string
    {
        $store = $this->container->bound(SettingStore::class) ? $this->container->make(SettingStore::class) : null;

        if ($store === null) {
            return null;
        }

        $value = $store instanceof ScopedSettingStore
            ? $store->own($this->settingKey($key), $teamId)
            : ($teamId === null ? $store->get($this->settingKey($key)) : null);

        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    /**
     * @throws UnknownPrompt
     */
    protected function assertWritable(string $key, string|int|null $teamId): PromptDefinition
    {
        $definition = $this->registry->get($key);

        if ($teamId !== null && ! $definition->teamOverridable) {
            throw new LogicException("The prompt [{$key}] only has a global version.");
        }

        return $definition;
    }

    protected function store(): SettingStore
    {
        $store = $this->container->bound(SettingStore::class) ? $this->container->make(SettingStore::class) : null;

        return $store
            ?? throw new LogicException('No setting store is bound: prompt overrides cannot be stored.');
    }

    protected function team(string|int|null $teamId): ?string
    {
        return $teamId === null ? null : (string) $teamId;
    }
}
