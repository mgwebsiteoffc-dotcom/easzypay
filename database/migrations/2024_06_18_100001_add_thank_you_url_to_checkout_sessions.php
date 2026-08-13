<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('checkout_sessions', 'shopify_thank_you_url')) {
                $table->string('shopify_thank_you_url', 500)->nullable()->after('shopify_order_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            $table->dropColumn('shopify_thank_you_url');
        });
    }
};