<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('episode_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('section_type');
            $table->string('title');
            $table->longText('text');
            $table->unsignedInteger('estimated_duration_seconds')->nullable();
            $table->unsignedInteger('sort_order');
            $table->json('raw_section_json')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'sort_order']);
            $table->index('section_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_sections');
    }
};
