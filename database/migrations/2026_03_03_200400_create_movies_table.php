<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('age_rating', 10)->nullable();
            $table->foreignId('language_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('vj_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
