<?php

namespace OiLab\OiLaravelAi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OiLab\OiLaravelAi\Database\Factories\AiRequestFactory;

class AiRequest extends Model
{
    /** @use HasFactory<AiRequestFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'prompt_type',
        'prompt_system',
        'prompt_input',
        'response',
        'tokens_input',
        'tokens_output',
        'tokens_cache_write',
        'tokens_cache_read',
        'tokens_reasoning',
        'duration',
        'prompt_schema',
        'project_id',
        'ai_provider_id',
        'ai_model_id',
        'agent_run_id',
    ];

    /**
     * @return BelongsTo<Model, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(config('oi-laravel-ai.models.project'));
    }

    /**
     * @return BelongsTo<AiProvider, $this>
     */
    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }

    /**
     * @return BelongsTo<AiModel, $this>
     */
    public function aiModel(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    /**
     * @return BelongsTo<Model, $this>
     */
    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(config('oi-laravel-ai.models.agent_run'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'prompt_schema' => 'array',
        ];
    }

    protected static function newFactory(): AiRequestFactory
    {
        return AiRequestFactory::new();
    }
}
