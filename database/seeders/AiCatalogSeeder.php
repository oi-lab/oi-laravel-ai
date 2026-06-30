<?php

namespace OiLab\OiLaravelAi\Database\Seeders;

use Illuminate\Database\Seeder;
use OiLab\OiLaravelAi\Contracts\SettingStore;
use OiLab\OiLaravelAi\Support\AiCatalog;
use OiLab\OiLaravelAi\Support\AiPromptVariableRegistry;

class AiCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $registry = AiCatalog::read();

        AiCatalog::sync($registry);

        $settings = app(SettingStore::class);

        if ($settings === null) {
            return;
        }

        $this->seedDefaultPrompts($settings);
    }

    private function seedDefaultPrompts(SettingStore $settings): void
    {
        foreach (AiPromptVariableRegistry::keys() as $key) {
            $settings->set(
                key: "PROMPT_SYSTEM.{$key}",
                value: AiPromptVariableRegistry::defaultPrompt($key),
                label: AiPromptVariableRegistry::label($key),
                type: 'string',
                teamId: null,
            );
        }
    }
}
