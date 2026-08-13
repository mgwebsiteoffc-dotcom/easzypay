<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('source')->default('stripe');
            $table->string('event_id')->nullable()->index();
            $table->string('event_type')->index();
            $table->enum('status', ['received', 'processed', 'failed', 'skipped'])->default('received');
            $table->json('payload')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};