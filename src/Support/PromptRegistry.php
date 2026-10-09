<?php

namespace OiLab\OiLaravelAi\Support;

use Closure;
use OiLab\OiLaravelAi\Data\AiPromptVariableData;
use OiLab\OiLaravelAi\Data\PromptDefinition;
use OiLab\OiLaravelAi\Exceptions\UnknownPrompt;

/**
 * The catalogue of editable prompts. The application and its packages define
 * theirs while booting; nothing is persisted here.
 *
 * Definitions are static, so the registry is safe to keep as a singleton under
 * a long-running server: it is filled at boot and only read afterwards.
 */
class PromptRegistry
{
    /**
     * @var array<string, PromptDefinition>
     */
    protected array $definitions = [];

    /**
     * @var array<string, array{variable: AiPromptVariableData, value: Closure(): ?string}>
     */
    protected array $globals = [];

    public function __construct()
    {
        $this->global(
            new AiPromptVariableData('app_name', "Nom de l'application", 'Laravel'),
            fn (): string => (string) config('app.name'),
        );

        $this->global(
            new AiPromptVariableData('today', 'Date du jour (AAAA-MM-JJ)', '2026-01-31'),
            fn (): string => now()->toDateString(),
        );
    }

    /**
     * Define (or redefine) one or more prompts.
     */
    public function define(PromptDefinition ...$definitions): static
    {
        foreach ($definitions as $definition) {
            $this->definitions[$definition->key] = $definition;
        }

        return $this;
    }

    /**
     * Declare a variable available to every prompt, with the closure that
     * computes its value at render time.
     *
     * @param  Closure(): ?string  $value
     */
    public function global(AiPromptVariableData $variable, Closure $value): static
    {
        $this->globals[$variable->name] = ['variable' => $variable, 'value' => $value];

        return $this;
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @throws UnknownPrompt
     */
    public function get(string $key): PromptDefinition
    {
        return $this->definitions[$key] ?? throw UnknownPrompt::key($key);
    }

    /**
     * Every definition, keyed by prompt key, in definition order.
     *
     * @return array<string, PromptDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return array_keys($this->definitions);
    }

    /**
     * @return array<int, AiPromptVariableData>
     */
    public function globals(): array
    {
        return array_values(array_map(fn (array $global): AiPromptVariableData => $global['variable'], $this->globals));
    }

    /**
     * @return array<string, string>
     */
    public function globalValues(): array
    {
        return array_map(fn (array $global): string => (string) ($global['value'])(), $this->globals);
    }
}
