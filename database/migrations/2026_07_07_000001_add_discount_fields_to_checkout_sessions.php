<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('checkout_sessions', 'discount_code')) {
                $table->string('discount_code')->nullable()->after('total_amount');
            }
            if (!Schema::hasColumn('checkout_sessions', 'discount_percent')) {
                $table->unsignedTinyInteger('discount_percent')->default(0)->after('discount_code');
            }
            if (!Schema::hasColumn('checkout_sessions', 'discount_amount')) {
                $table->unsignedBigInteger('discount_amount')->default(0)->after('discount_percent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('checkout_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('checkout_sessions', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
            if (Schema::hasColumn('checkout_sessions', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('checkout_sessions', 'discount_code')) {
                $table->dropColumn('discount_code');
            }
        });
    }
};
