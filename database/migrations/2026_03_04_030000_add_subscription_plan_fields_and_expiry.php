<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_status')->index();
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->string('plan_code', 50)->nullable()->after('currency')->index();
            $table->string('plan_name', 100)->nullable()->after('plan_code');
            $table->unsignedInteger('plan_days')->nullable()->after('plan_name');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropColumn(['plan_code', 'plan_name', 'plan_days']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('subscription_expires_at');
        });
    }
};
