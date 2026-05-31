<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('topic_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('topic_id');
            $table->foreignId('latest_episode_topic_id')->nullable()->constrained('episode_topics')->nullOnDelete();
            $table->smallInteger('rating');
            $table->dateTime('rated_at')->nullable();
            $table->dateTime('synced_at')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic_id']);
            $table->index('topic_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_ratings');
    }
};
