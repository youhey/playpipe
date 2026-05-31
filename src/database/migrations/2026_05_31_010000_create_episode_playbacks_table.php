<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('episode_playbacks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->dateTime('first_played_at')->nullable();
            $table->dateTime('last_played_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('play_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'episode_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_playbacks');
    }
};
