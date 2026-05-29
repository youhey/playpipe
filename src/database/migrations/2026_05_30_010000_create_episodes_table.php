<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table): void {
            $table->id();
            $table->string('episode_key')->unique();
            $table->string('status')->default('available')->index();
            $table->string('title');
            $table->string('language')->default('ja');
            $table->string('character_key')->nullable();
            $table->string('character_name')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->dateTime('processed_at')->nullable();
            $table->dateTime('recorded_at')->nullable()->index();
            $table->string('audio_disk')->default('s3');
            $table->string('audio_path');
            $table->unsignedBigInteger('audio_size_bytes')->nullable();
            $table->unsignedInteger('audio_duration_seconds')->nullable();
            $table->string('voicepipe_version')->nullable();
            $table->json('episode_json');
            $table->json('scenario_json');
            $table->json('render_metadata_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
