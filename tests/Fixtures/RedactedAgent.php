<?php

namespace OiLab\OiLaravelAi\Tests\Fixtures;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use OiLab\OiLaravelAi\Contracts\HasPromptType;

class RedactedAgent implements Agent, HasPromptType
{
    use Promptable;

    public function instructions(): string
    {
        return 'You write a prospecting email.';
    }

    public function promptType(): string
    {
        return 'crm.mail';
    }
}
