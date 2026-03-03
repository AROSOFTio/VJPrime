<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 50)->default('pesapal')->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('UGX');
            $table->string('status', 30)->default('pending')->index();
            $table->string('reference')->unique();
            $table->string('merchant_reference')->nullable()->index();
            $table->string('order_tracking_id')->nullable()->index();
            $table->string('pesapal_tracking_id')->nullable();
            $table->string('redirect_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('ipn_id')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('status_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
