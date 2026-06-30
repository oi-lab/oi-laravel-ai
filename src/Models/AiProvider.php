<?php

namespace OiLab\OiLaravelAi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OiLab\OiLaravelAi\Database\Factories\AiProviderFactory;

class AiProvider extends Model
{
    /** @use HasFactory<AiProviderFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'owner'];

    /**
     * @return HasMany<AiModel, $this>
     */
    public function aiModels(): HasMany
    {
        return $this->hasMany(AiModel::class);
    }

    protected static function newFactory(): AiProviderFactory
    {
        return AiProviderFactory::new();
    }
}
