<?php

namespace OiLab\OiLaravelAi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelAi\Models\AiModel;
use OiLab\OiLaravelAi\Models\AiProvider;

/**
 * @extends Factory<AiModel>
 */
class AiModelFactory extends Factory
{
    protected $model = AiModel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(3),
            'name' => fake()->words(2, true),
            'type' => 'text',
            'cost_input' => fake()->randomFloat(2, 0, 20),
            'cost_output' => fake()->randomFloat(2, 0, 80),
            'ai_provider_id' => AiProvider::factory(),
        ];
    }
}
