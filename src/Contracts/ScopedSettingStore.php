<?php

namespace OiLab\OiLaravelAi\Contracts;

/**
 * A {@see SettingStore} able to read one layer only. The prompt manager needs
 * it to tell a team's own override from the global one it would fall back to.
 */
interface ScopedSettingStore extends SettingStore
{
    /**
     * The value owned by the given team (null = global), without falling back
     * to the global layer.
     */
    public function own(string $key, ?string $teamId = null): mixed;
}
