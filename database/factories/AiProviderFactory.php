<?php

namespace OiLab\OiLaravelAi\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelAi\Models\AiProvider;

/**
 * @extends Factory<AiProvider>
 */
class AiProviderFactory extends Factory
{
    protected $model = AiProvider::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'code' => str($name)->slug(),
            'name' => ucfirst($name),
            'owner' => fake()->company(),
        ];
    }
}
