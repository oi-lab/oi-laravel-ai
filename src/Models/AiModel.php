<?php

namespace OiLab\OiLaravelAi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OiLab\OiLaravelAi\Database\Factories\AiModelFactory;

class AiModel extends Model
{
    /** @use HasFactory<AiModelFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'type', 'cost_input', 'cost_output', 'ai_provider_id'];

    /**
     * @return BelongsTo<AiProvider, $this>
     */
    public function aiProvider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }

    /**
     * @return HasMany<AiRequest, $this>
     */
    public function aiRequests(): HasMany
    {
        return $this->hasMany(AiRequest::class);
    }

    protected static function newFactory(): AiModelFactory
    {
        return AiModelFactory::new();
    }
}
