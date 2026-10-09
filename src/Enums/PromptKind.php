<?php

namespace OiLab\OiLaravelAi\Enums;

/**
 * What part of a call a prompt template writes.
 */
enum PromptKind: string
{
    /** The system instructions of an agent. */
    case System = 'system';

    /** The message sent to an agent, built around the data of the call. */
    case Message = 'message';

    /** The instructions an MCP server gives to the assistants it serves. */
    case Mcp = 'mcp';
}
