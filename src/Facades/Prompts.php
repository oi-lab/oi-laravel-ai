<?php

namespace OiLab\OiLaravelAi\Facades;

use Illuminate\Support\Facades\Facade;
use OiLab\OiLaravelAi\Services\PromptManager;

/**
 * @method static \OiLab\OiLaravelAi\Support\PromptRegistry registry()
 * @method static string settingKey(string $key)
 * @method static string template(string $key, string|int|null $teamId = null)
 * @method static \OiLab\OiLaravelAi\Enums\PromptSource source(string $key, string|int|null $teamId = null)
 * @method static string render(string $key, array $variables = [], string|int|null $teamId = null)
 * @method static string preview(string $key, string|int|null $teamId = null, ?string $template = null)
 * @method static string fingerprint(string $key, string|int|null $teamId = null)
 * @method static void save(string $key, string $template, string|int|null $teamId = null)
 * @method static void reset(string $key, string|int|null $teamId = null)
 * @method static string compile(string $template, array $variables = [])
 *
 * @see PromptManager
 */
class Prompts extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PromptManager::class;
    }
}
