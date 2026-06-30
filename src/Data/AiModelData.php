<?php

namespace OiLab\OiLaravelAi\Data;

use OiLab\OiLaravelAi\Models\AiModel;
use Spatie\LaravelData\Data;

class AiModelData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly string $type,
        public readonly float $cost_input,
        public readonly float $cost_output,
        public readonly AiProviderData $ai_provider,
    ) {}

    public static function fromModel(AiModel $model): self
    {
        return new self(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            type: $model->type,
            cost_input: $model->cost_input,
            cost_output: $model->cost_output,
            ai_provider: new AiProviderData(
                id: $model->aiProvider->id,
                code: $model->aiProvider->code,
                name: $model->aiProvider->name,
            ),
        );
    }
}
