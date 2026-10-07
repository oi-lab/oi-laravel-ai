<?php

namespace OiLab\OiLaravelAi\Support;

/**
 * Measures the duration of an agent invocation between the `PromptingAgent` and
 * `AgentPrompted` events. Registered as a scoped binding so no start time leaks
 * between Octane requests or queue jobs.
 */
class InvocationTimer
{
    /** @var array<string, int> */
    private array $startedAt = [];

    public function start(string $invocationId): void
    {
        $this->startedAt[$invocationId] = hrtime(true);
    }

    /**
     * Elapsed milliseconds since start(), forgetting the invocation; 0 when it
     * was never started.
     */
    public function stop(string $invocationId): int
    {
        $start = $this->startedAt[$invocationId] ?? null;
        unset($this->startedAt[$invocationId]);

        return $start === null ? 0 : (int) round((hrtime(true) - $start) / 1_000_000);
    }
}
