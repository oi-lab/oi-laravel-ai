<?php

use OiLab\OiLaravelAi\Support\AiCatalog;

it('decodes a valid registry payload', function () {
    $registry = AiCatalog::decode(json_encode([
        'providers' => [['code' => 'anthropic', 'name' => 'Anthropic']],
        'models' => [['code' => 'claude', 'name' => 'Claude', 'provider' => 'anthropic']],
    ]));

    expect($registry['providers'])->toHaveCount(1)
        ->and($registry['models'])->toHaveCount(1)
        ->and($registry)->not->toHaveKey('default_models');
});

it('throws when the payload is missing providers or models', function () {
    AiCatalog::decode(json_encode(['providers' => []]));
})->throws(InvalidArgumentException::class);

it('throws when reading a registry file that does not exist', function () {
    AiCatalog::read('/path/that/does/not/exist.json');
})->throws(InvalidArgumentException::class);

it('reads the bundled registry asset', function () {
    expect(AiCatalog::defaultPath())->toEndWith('assets/registry.json')
        ->and(is_file(AiCatalog::defaultPath()))->toBeTrue();

    $registry = AiCatalog::read(AiCatalog::defaultPath());

    expect($registry['providers'])->not->toBeEmpty()
        ->and($registry['models'])->not->toBeEmpty();
});
