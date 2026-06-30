<?php

namespace OiLab\OiLaravelAi\Data;

use Spatie\LaravelData\Data;

class AiUsageSummaryData extends Data
{
    public function __construct(
        public readonly int $total_requests,
        public readonly int $total_tokens_input,
        public readonly int $total_tokens_output,
        public readonly float $estimated_cost_usd,
        /** @var array<int, array<string, mixed>> */
        public readonly array $by_agent_type,
        /** @var array<int, array<string, mixed>> */
        public readonly array $by_model,
    ) {}
}
