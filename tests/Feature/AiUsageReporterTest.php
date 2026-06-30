<?php

use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiRequest;
use OiLab\OiLaravelAi\Services\AiUsageReporter;
use OiLab\OiLaravelAi\Tests\Fixtures\Project;

beforeEach(function () {
    $this->reporter = app(AiUsageReporter::class);
});

it('computes the cost of a single request from its model pricing', function () {
    $model = AiModel::factory()->create(['cost_input' => 3.0, 'cost_output' => 15.0]);

    $request = AiRequest::factory()->create([
        'ai_model_id' => $model->id,
        'ai_provider_id' => $model->ai_provider_id,
        'tokens_input' => 1_000_000,
        'tokens_output' => 2_000_000,
    ]);

    // (1M / 1M * 3) + (2M / 1M * 15) = 33
    expect($this->reporter->costForRequest($request->fresh('aiModel')))->toBe(33.0);
});

it('returns zero cost for a request without a model', function () {
    $request = AiRequest::factory()->create(['ai_model_id' => null]);

    expect($this->reporter->costForRequest($request->fresh()))->toBe(0.0);
});

it('builds a usage summary for a set of projects over a period', function () {
    $project = Project::create(['name' => 'Acme']);
    $model = AiModel::factory()->create(['cost_input' => 1.0, 'cost_output' => 2.0]);

    AiRequest::factory()->count(2)->create([
        'project_id' => $project->id,
        'ai_model_id' => $model->id,
        'ai_provider_id' => $model->ai_provider_id,
        'prompt_type' => 'DocWriterAgent',
        'tokens_input' => 1_000_000,
        'tokens_output' => 1_000_000,
    ]);

    // A request for another project must be excluded.
    AiRequest::factory()->create(['project_id' => null]);

    $summary = $this->reporter->summaryForProjects([$project->id]);

    expect($summary->total_requests)->toBe(2)
        ->and($summary->total_tokens_input)->toBe(2_000_000)
        ->and($summary->total_tokens_output)->toBe(2_000_000)
        ->and($summary->estimated_cost_usd)->toBe(6.0) // 2 * (1 + 2)
        ->and($summary->by_agent_type)->toHaveCount(1)
        ->and($summary->by_model)->toHaveCount(1);

    expect($summary->by_agent_type[0]['prompt_type'])->toBe('DocWriterAgent');
    expect($summary->by_model[0]['model_name'])->toBe($model->name);
});

it('excludes requests outside the given period', function () {
    $project = Project::create(['name' => 'Acme']);

    $request = AiRequest::factory()->create([
        'project_id' => $project->id,
        'tokens_input' => 500,
    ]);
    $request->forceFill(['created_at' => now()->subYear()])->save();

    $summary = $this->reporter->summaryForProjects(
        [$project->id],
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($summary->total_requests)->toBe(0);
});
