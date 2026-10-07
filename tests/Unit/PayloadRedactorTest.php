<?php

use OiLab\OiLaravelAi\Support\PayloadRedactor;

it('keeps payloads for unlisted prompt types', function () {
    config()->set('oi-laravel-ai.redacted_prompt_types', ['crm.mail']);

    expect((new PayloadRedactor)->payload('Bonjour', 'TrackedAgent'))->toBe('Bonjour');
});

it('truncates kept payloads', function () {
    expect((new PayloadRedactor)->payload(str_repeat('a', 20000), 'TrackedAgent'))->toBe(str_repeat('a', 10000).'...');
});

it('redacts payloads for listed prompt types, wildcards included', function () {
    config()->set('oi-laravel-ai.redacted_prompt_types', ['crm.mail', 'private.*']);
    $redactor = new PayloadRedactor;

    expect($redactor->payload('Bonjour é', 'crm.mail'))->toBe('sha256:'.hash('sha256', 'Bonjour é').' (9 car.)')
        ->and($redactor->payload('x', 'private.reply'))->toStartWith('sha256:')
        ->and($redactor->payload(null, 'crm.mail'))->toBeNull();
});

it('redacts every payload when store_payloads is false', function () {
    config()->set('oi-laravel-ai.store_payloads', false);

    expect((new PayloadRedactor)->payload('Bonjour', 'TrackedAgent'))->toBe('sha256:'.hash('sha256', 'Bonjour').' (7 car.)');
});
