<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('content_type', 20)->default('movie')->after('slug')->index();
            $table->string('series_title')->nullable()->after('content_type');
            $table->unsignedSmallInteger('season_number')->nullable()->after('series_title');
            $table->unsignedSmallInteger('episode_number')->nullable()->after('season_number');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'content_type',
                'series_title',
                'season_number',
                'episode_number',
            ]);
        });
    }
};
