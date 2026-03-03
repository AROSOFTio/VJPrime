<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('hls_master_path');
            $table->string('preview_clip_path')->nullable();
            $table->string('download_file_path')->nullable();
            $table->json('renditions_json')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->unique('movie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_assets');
    }
};
