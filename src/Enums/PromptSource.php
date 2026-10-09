<?php

namespace OiLab\OiLaravelAi\Enums;

/**
 * Where the template of a prompt comes from, for a given team.
 */
enum PromptSource: string
{
    /** The template shipped with the definition. */
    case Default = 'default';

    /** The global override. */
    case Global = 'global';

    /** The override of the requested team. */
    case Team = 'team';
}
