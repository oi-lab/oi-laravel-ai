<?php

namespace OiLab\OiLaravelAi\Data;

use OiLab\OiLaravelAi\Enums\PromptKind;

/**
 * An editable prompt declared by the application or a package: its key, the
 * template shipped by default (with `:variable` placeholders) and the variables
 * the code fills when rendering it.
 *
 * A prompt that is not team-overridable only has a global version: a team never
 * reads nor writes its own.
 */
readonly class PromptDefinition
{
    /**
     * @param  array<int, AiPromptVariableData>  $variables
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $default,
        public array $variables = [],
        public ?string $group = null,
        public PromptKind $kind = PromptKind::System,
        public ?string $description = null,
        public bool $teamOverridable = true,
    ) {}

    /**
     * @return array{key: string, label: string, group: ?string, kind: string, description: ?string, team_overridable: bool, variables: array<int, array{name: string, description: string, example: ?string}>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'group' => $this->group,
            'kind' => $this->kind->value,
            'description' => $this->description,
            'team_overridable' => $this->teamOverridable,
            'variables' => array_map(fn (AiPromptVariableData $variable): array => $variable->toArray(), $this->variables),
        ];
    }
}
