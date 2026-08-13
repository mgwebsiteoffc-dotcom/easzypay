<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency', 3)->index();
            $table->string('to_currency', 3)->index();
            $table->decimal('rate', 12, 6);
            $table->string('source')->default('open.er-api.com');
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['from_currency', 'to_currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};