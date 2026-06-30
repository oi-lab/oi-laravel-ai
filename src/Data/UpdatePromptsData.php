<?php

namespace OiLab\OiLaravelAi\Data;

use Spatie\LaravelData\Data;

class UpdatePromptsData extends Data
{
    public function __construct(
        /** @var array<string, string> */
        public readonly array $prompts,
    ) {}
}
