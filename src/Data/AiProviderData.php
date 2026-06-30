<?php

namespace OiLab\OiLaravelAi\Data;

use Spatie\LaravelData\Data;

class AiProviderData extends Data
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
    ) {}
}
