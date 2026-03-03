<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('seconds_watched')->default(0);
            $table->string('device_hash', 64)->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['movie_id', 'created_at']);
            $table->index(['user_id', 'movie_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('views');
    }
};
