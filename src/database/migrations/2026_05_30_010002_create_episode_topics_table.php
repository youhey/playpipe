<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('episode_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('topic_id')->nullable()->index();
            $table->string('status')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->text('why_it_matters')->nullable();
            $table->string('source_name')->nullable()->index();
            $table->text('url')->nullable();
            $table->text('discussion_url')->nullable();
            $table->unsignedInteger('sort_order');
            $table->json('raw_topic_json')->nullable();
            $table->timestamps();

            $table->index(['episode_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_topics');
    }
};
