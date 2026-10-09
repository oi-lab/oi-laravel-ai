<?php

namespace OiLab\OiLaravelAi\Exceptions;

use InvalidArgumentException;

class UnknownPrompt extends InvalidArgumentException
{
    public static function key(string $key): self
    {
        return new self("No prompt is defined under the key [{$key}].");
    }
}
