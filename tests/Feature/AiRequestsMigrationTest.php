<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OiLab\OiLaravelAi\Models\AiRequest;

/**
 * Re-run the ai_requests migration against a host with (or without) the
 * optional tables, once the default test schema has been set up.
 */
function migrateAiRequests(): void
{
    Schema::dropIfExists('ai_requests');

    /** @var Migration $migration */
    $migration = require __DIR__.'/../../database/migrations/2026_06_02_185811_create_ai_requests_table.php';
    $migration->up();
}

/**
 * @return array<int, string>
 */
function aiRequestForeignTables(): array
{
    return collect(Schema::getForeignKeys('ai_requests'))->pluck('foreign_table')->sort()->values()->all();
}

it('installs without projects and agent_runs tables', function () {
    Schema::dropIfExists('ai_requests');
    Schema::dropIfExists('projects');
    Schema::dropIfExists('agent_runs');
    config()->set('oi-laravel-ai.relations', ['project' => null, 'agent_run' => null]);

    migrateAiRequests();

    expect(aiRequestForeignTables())->toBe(['ai_models', 'ai_providers']);

    $request = AiRequest::create([
        'prompt_type' => 'TrackedAgent',
        'project_id' => (string) Str::uuid(),
    ]);

    expect($request->exists)->toBeTrue()
        ->and($request->fresh()->project)->toBeNull()
        ->and($request->fresh()->agentRun)->toBeNull()
        ->and(AiRequest::with(['project', 'agentRun'])->first()->project)->toBeNull();
});

it('does not add a foreign key to a configured table that does not exist', function () {
    Schema::dropIfExists('ai_requests');
    Schema::dropIfExists('projects');
    config()->set('oi-laravel-ai.relations.project', 'projects');

    migrateAiRequests();

    expect(aiRequestForeignTables())->toBe(['agent_runs', 'ai_models', 'ai_providers']);
});

it('adds foreign keys when the relations are configured and the tables exist', function () {
    migrateAiRequests();

    expect(aiRequestForeignTables())->toBe(['agent_runs', 'ai_models', 'ai_providers', 'projects']);
});

it('keeps the project and agent_run columns indexed', function () {
    migrateAiRequests();

    $indexed = collect(Schema::getIndexes('ai_requests'))->pluck('columns')->map(fn (array $columns) => $columns[0])->all();

    expect($indexed)->toContain('project_id', 'agent_run_id', 'prompt_type');
});

it('nulls the project of a request when the project is deleted', function () {
    $projectId = (string) Str::uuid();
    DB::table('projects')->insert(['id' => $projectId, 'created_at' => now(), 'updated_at' => now()]);
    $request = AiRequest::create(['prompt_type' => 'TrackedAgent', 'project_id' => $projectId]);

    DB::table('projects')->where('id', $projectId)->delete();

    expect($request->fresh()->project_id)->toBeNull();
});
