<?php

use OiLab\OiLaravelAi\Data\AiPromptVariableData;
use OiLab\OiLaravelAi\Support\AiPromptVariableRegistry;

it('substitutes :variable placeholders when compiling a prompt', function () {
    $compiled = AiPromptVariableRegistry::compile(
        'Projet : :project_name en :language',
        ['project_name' => 'Acme', 'language' => 'français'],
    );

    expect($compiled)->toBe('Projet : Acme en français');
});

it('injects the app_name global automatically', function () {
    config()->set('app.name', 'OiTest');

    $compiled = AiPromptVariableRegistry::compile('Bienvenue sur :app_name');

    expect($compiled)->toBe('Bienvenue sur OiTest');
});

it('exposes every agent key with a label, prompt and variables', function () {
    $keys = AiPromptVariableRegistry::keys();

    expect($keys)
        ->toContain(AiPromptVariableRegistry::DOC_WRITER)
        ->toContain(AiPromptVariableRegistry::COMMIT_ANALYZER)
        ->toHaveCount(13);

    foreach ($keys as $key) {
        expect(AiPromptVariableRegistry::label($key))->toBeString()->not->toBe('');
        expect(AiPromptVariableRegistry::defaultPrompt($key))->toBeString()->not->toBe('');
        expect(AiPromptVariableRegistry::variablesFor($key))->toBeArray();
    }
});

it('returns variable definitions as AiPromptVariableData objects', function () {
    $variables = AiPromptVariableRegistry::variablesFor(AiPromptVariableRegistry::DOC_WRITER);

    expect($variables)->each->toBeInstanceOf(AiPromptVariableData::class);
    expect(collect($variables)->pluck('name'))->toContain('section_title');
});

it('declares app_name as a global variable', function () {
    expect(collect(AiPromptVariableRegistry::globals())->pluck('name'))->toContain('app_name');
});
