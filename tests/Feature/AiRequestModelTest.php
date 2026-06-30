<?php

use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelAi\Tests\Fixtures\Project;

it('persists a request with its catalog relations', function () {
    $request = AiRequest::factory()->create();

    expect($request->aiModel)->not->toBeNull()
        ->and($request->aiProvider)->not->toBeNull()
        ->and($request->aiModel->aiProvider->is($request->aiProvider))->toBeTrue();
});

it('does not maintain an updated_at timestamp', function () {
    $request = AiRequest::factory()->create();

    expect($request->updated_at)->toBeNull();
});

it('resolves the project relation through the configured host model', function () {
    $project = Project::create(['name' => 'Acme']);

    $request = AiRequest::factory()->create(['project_id' => $project->id]);

    expect($request->project)->toBeInstanceOf(Project::class)
        ->and($request->project->id)->toBe($project->id);
});

it('casts the prompt schema to an array', function () {
    $request = AiRequest::factory()->create(['prompt_schema' => ['type' => 'object']]);

    expect($request->fresh()->prompt_schema)->toBe(['type' => 'object']);
});
