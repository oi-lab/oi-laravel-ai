<?php

use App\Models\AgentRun;
use App\Models\Project;

return [

    /*
    |--------------------------------------------------------------------------
    | Host application models
    |--------------------------------------------------------------------------
    |
    | The AiRequest model references the host application's Project and AgentRun
    | models through these bindings (UUID foreign keys). Override them so the
    | package adapts to the host's domain models without being coupled to them.
    |
    */

    'models' => [
        'project' => env('OI_AI_PROJECT_MODEL', Project::class),
        'agent_run' => env('OI_AI_AGENT_RUN_MODEL', AgentRun::class),
    ],

    /*
    |--------------------------------------------------------------------------
    | Setting store
    |--------------------------------------------------------------------------
    |
    | The package reads and writes team-scoped / global settings (default model
    | per agent, system prompt overrides) through the SettingStore contract.
    | Leave null to auto-detect: when oi-lab/oi-laravel-settings is installed its
    | adapter is wired automatically. Bind a class here to use a custom store, or
    | set an empty value to disable settings seeding (catalog seeding still runs).
    |
    */

    'setting_store' => env('OI_AI_SETTING_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Team context binding
    |--------------------------------------------------------------------------
    |
    | Container binding resolving the "current team" used to scope settings. The
    | host application is responsible for binding it.
    |
    */

    'context_binding' => 'current.team',

    /*
    |--------------------------------------------------------------------------
    | Catalog registry
    |--------------------------------------------------------------------------
    |
    | The provider/model catalog and pricing live in a JSON registry shipped
    | with the package (assets/registry.json). `path` may point to a writable,
    | host-owned copy used by the seeder and the `ai:update-registry` command;
    | when null the bundled asset is used. `url` is the canonical raw location
    | the update command fetches a fresh registry from.
    |
    */

    'registry' => [
        'path' => env('OI_AI_REGISTRY_PATH'),
        'url' => env('OI_AI_REGISTRY_URL'),
    ],

];
