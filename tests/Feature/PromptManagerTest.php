<?php

use OiLab\OiLaravelAi\Contracts\SettingStore;
use OiLab\OiLaravelAi\Data\AiPromptVariableData;
use OiLab\OiLaravelAi\Data\PromptDefinition;
use OiLab\OiLaravelAi\Enums\PromptKind;
use OiLab\OiLaravelAi\Enums\PromptSource;
use OiLab\OiLaravelAi\Exceptions\UnknownPrompt;
use OiLab\OiLaravelAi\Facades\Prompts;
use OiLab\OiLaravelAi\Support\PromptRegistry;
use OiLab\OiLaravelAi\Tests\Fixtures\ArrayScopedSettingStore;

beforeEach(function () {
    config()->set('app.name', 'Desk');

    $this->store = new ArrayScopedSettingStore;
    app()->instance(SettingStore::class, $this->store);

    app(PromptRegistry::class)->define(
        new PromptDefinition(
            key: 'desk.analyse.system',
            label: 'Analyse',
            default: 'Tu analyses pour :app_name. Types : :types',
            variables: [new AiPromptVariableData('types', 'Types', '- BUG')],
            group: 'Desk',
        ),
        new PromptDefinition(
            key: 'crm.mail.system',
            label: 'Mail',
            default: 'Défaut',
            kind: PromptKind::Message,
            teamOverridable: false,
        ),
    );
});

it('renders the default template when nothing is stored', function () {
    expect(Prompts::render('desk.analyse.system', ['types' => '- A'], 7))->toBe('Tu analyses pour Desk. Types : - A')
        ->and(Prompts::source('desk.analyse.system', 7))->toBe(PromptSource::Default);
});

it('reads the team override, then the global one, then the default', function () {
    Prompts::save('desk.analyse.system', 'Global');
    Prompts::save('desk.analyse.system', 'Équipe', 7);

    expect(Prompts::template('desk.analyse.system', 7))->toBe('Équipe')
        ->and(Prompts::source('desk.analyse.system', 7))->toBe(PromptSource::Team)
        ->and(Prompts::template('desk.analyse.system', 8))->toBe('Global')
        ->and(Prompts::source('desk.analyse.system', 8))->toBe(PromptSource::Global)
        ->and($this->store->layers['7'])->toHaveKey('PROMPTS.desk.analyse.system');
});

it('falls back to the next layer once an override is reset', function () {
    Prompts::save('desk.analyse.system', 'Global');
    Prompts::save('desk.analyse.system', 'Équipe', 7);

    Prompts::reset('desk.analyse.system', 7);
    expect(Prompts::template('desk.analyse.system', 7))->toBe('Global');

    Prompts::reset('desk.analyse.system');
    expect(Prompts::source('desk.analyse.system', 7))->toBe(PromptSource::Default);
});

it('treats a blank override as absent', function () {
    Prompts::save('desk.analyse.system', '   ');

    expect(Prompts::source('desk.analyse.system'))->toBe(PromptSource::Default);
});

it('ignores and refuses a team version of a global-only prompt', function () {
    $this->store->set('PROMPTS.crm.mail.system', 'Équipe', 'Mail', teamId: '7');

    expect(Prompts::template('crm.mail.system', 7))->toBe('Défaut');

    Prompts::save('crm.mail.system', 'X', 7);
})->throws(LogicException::class);

it('never expands a placeholder found inside a value', function () {
    expect(Prompts::render('desk.analyse.system', ['types' => ':app_name']))->toBe('Tu analyses pour Desk. Types : :app_name');
});

it('previews with the example values and fingerprints the template', function () {
    expect(Prompts::preview('desk.analyse.system'))->toBe('Tu analyses pour Desk. Types : - BUG')
        ->and(Prompts::preview('desk.analyse.system', template: ':types'))->toBe('- BUG');

    $before = Prompts::fingerprint('desk.analyse.system');
    Prompts::save('desk.analyse.system', 'Autre');

    expect(Prompts::fingerprint('desk.analyse.system'))->toHaveLength(8)->not->toBe($before);
});

it('keeps the defaults when no setting store is bound', function () {
    app()->bind(SettingStore::class, fn () => null);

    expect(Prompts::render('desk.analyse.system', ['types' => 'x']))->toBe('Tu analyses pour Desk. Types : x');

    Prompts::save('desk.analyse.system', 'X');
})->throws(LogicException::class);

it('throws on an unknown key', function () {
    Prompts::render('nope');
})->throws(UnknownPrompt::class);
