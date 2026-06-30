<?php

namespace OiLab\OiLaravelAi\Data;

readonly class AiPromptVariableData
{
    public function __construct(
        public string $name,
        public string $description,
        public ?string $example = null,
    ) {}

    /**
     * @return array{name: string, description: string, example: ?string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'example' => $this->example,
        ];
    }
}
