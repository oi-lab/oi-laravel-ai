<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_requests', function (Blueprint $table) {
            $table->id();
            $table->string('prompt_type');
            $table->longText('prompt_system')->nullable();
            $table->longText('prompt_input')->nullable();
            $table->longText('response')->nullable();
            $table->unsignedInteger('tokens_input')->default(0);
            $table->unsignedInteger('tokens_output')->default(0);
            $table->unsignedInteger('tokens_cache_write')->default(0);
            $table->unsignedInteger('tokens_cache_read')->default(0);
            $table->unsignedInteger('tokens_reasoning')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->json('prompt_schema')->nullable();
            $table->foreignUuid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ai_model_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('agent_run_id')->nullable()->constrained('agent_runs')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index('prompt_type');
            $table->index(['project_id', 'created_at']);
            $table->index(['ai_model_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_requests');
    }
};
