<?php

namespace OiLab\OiLaravelAi\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use OiLab\OiLaravelAi\Data\AiUsageSummaryData;
use OiLab\OiLaravelAi\Models\AiRequest;

/**
 * Centralizes the AI consumption / cost calculations previously living in the
 * AiUsageController: cost estimation per request and aggregation by agent type
 * and by model over a period.
 */
class AiUsageReporter
{
    /**
     * Build the usage summary for the given projects over a period (defaults to
     * the current month).
     *
     * @param  iterable<int, int|string>  $projectIds
     */
    public function summaryForProjects(
        iterable $projectIds,
        ?CarbonInterface $start = null,
        ?CarbonInterface $end = null,
    ): AiUsageSummaryData {
        $start ??= Carbon::now()->startOfMonth();
        $end ??= Carbon::now()->endOfMonth();

        $requests = AiRequest::whereIn('project_id', $projectIds)
            ->with(['aiModel.aiProvider'])
            ->whereBetween('created_at', [$start, $end])
            ->get();

        return new AiUsageSummaryData(
            total_requests: $requests->count(),
            total_tokens_input: (int) $requests->sum('tokens_input'),
            total_tokens_output: (int) $requests->sum('tokens_output'),
            estimated_cost_usd: round($this->estimatedCost($requests), 4),
            by_agent_type: $this->groupByAgentType($requests),
            by_model: $this->groupByModel($requests),
        );
    }

    /**
     * Estimated cost in USD for a single request based on its model's per-1M
     * token pricing.
     */
    public function costForRequest(AiRequest $request): float
    {
        if (! $request->aiModel) {
            return 0;
        }

        $inputCost = ($request->tokens_input / 1_000_000) * $request->aiModel->cost_input;
        $outputCost = ($request->tokens_output / 1_000_000) * $request->aiModel->cost_output;

        return $inputCost + $outputCost;
    }

    /**
     * Total estimated cost in USD over a collection of requests.
     *
     * @param  Collection<int, AiRequest>  $requests
     */
    public function estimatedCost(Collection $requests): float
    {
        return $requests->sum(fn (AiRequest $request): float => $this->costForRequest($request));
    }

    /**
     * @param  Collection<int, AiRequest>  $requests
     * @return array<int, array<string, mixed>>
     */
    public function groupByAgentType(Collection $requests): array
    {
        return $requests->groupBy('prompt_type')->map(function (Collection $group, string $type): array {
            return [
                'prompt_type' => $type,
                'count' => $group->count(),
                'tokens_input' => (int) $group->sum('tokens_input'),
                'tokens_output' => (int) $group->sum('tokens_output'),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, AiRequest>  $requests
     * @return array<int, array<string, mixed>>
     */
    public function groupByModel(Collection $requests): array
    {
        return $requests->groupBy('ai_model_id')->map(function (Collection $group): array {
            $model = $group->first()->aiModel;

            return [
                'model_name' => $model?->name ?? 'Unknown',
                'count' => $group->count(),
                'tokens_input' => (int) $group->sum('tokens_input'),
                'tokens_output' => (int) $group->sum('tokens_output'),
            ];
        })->values()->all();
    }
}
