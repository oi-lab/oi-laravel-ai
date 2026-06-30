<?php

namespace OiLab\OiLaravelAi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiRequest;

/**
 * @extends Factory<AiRequest>
 */
class AiRequestFactory extends Factory
{
    protected $model = AiRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $model = AiModel::factory()->create();

        return [
            'prompt_type' => fake()->randomElement(['DocWriterAgent', 'DocReviewerAgent', 'CommitAnalyzerAgent']),
            'prompt_system' => fake()->sentence(),
            'prompt_input' => fake()->sentence(),
            'response' => fake()->paragraph(),
            'tokens_input' => fake()->numberBetween(100, 10000),
            'tokens_output' => fake()->numberBetween(100, 5000),
            'tokens_cache_write' => 0,
            'tokens_cache_read' => 0,
            'tokens_reasoning' => 0,
            'duration' => fake()->numberBetween(0, 5000),
            'prompt_schema' => null,
            'project_id' => null,
            'ai_provider_id' => $model->ai_provider_id,
            'ai_model_id' => $model->id,
            'agent_run_id' => null,
        ];
    }
}
