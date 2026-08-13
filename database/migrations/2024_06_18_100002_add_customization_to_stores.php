<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Button customization
            if (!Schema::hasColumn('stores', 'button_text')) {
                $table->string('button_text')->default('⚡ Buy Now — Secure Checkout');
            }
            if (!Schema::hasColumn('stores', 'button_bg_start')) {
                $table->string('button_bg_start', 7)->default('#667eea');
            }
            if (!Schema::hasColumn('stores', 'button_bg_end')) {
                $table->string('button_bg_end', 7)->default('#764ba2');
            }
            if (!Schema::hasColumn('stores', 'button_text_color')) {
                $table->string('button_text_color', 7)->default('#ffffff');
            }
            if (!Schema::hasColumn('stores', 'button_border_radius')) {
                $table->string('button_border_radius', 10)->default('8px');
            }
            if (!Schema::hasColumn('stores', 'button_style')) {
                $table->enum('button_style', ['gradient', 'solid', 'outline'])->default('gradient');
            }
            if (!Schema::hasColumn('stores', 'show_trust_badges')) {
                $table->boolean('show_trust_badges')->default(true);
            }

            // Checkout customization
            if (!Schema::hasColumn('stores', 'checkout_logo')) {
                $table->string('checkout_logo', 500)->nullable();
            }
            if (!Schema::hasColumn('stores', 'checkout_primary_color')) {
                $table->string('checkout_primary_color', 7)->default('#667eea');
            }
            if (!Schema::hasColumn('stores', 'checkout_accent_color')) {
                $table->string('checkout_accent_color', 7)->default('#764ba2');
            }
            if (!Schema::hasColumn('stores', 'checkout_font_family')) {
                $table->string('checkout_font_family')->default('-apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif');
            }
            if (!Schema::hasColumn('stores', 'checkout_header_text')) {
                $table->string('checkout_header_text')->nullable();
            }
            if (!Schema::hasColumn('stores', 'checkout_footer_text')) {
                $table->text('checkout_footer_text')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $cols = ['button_text','button_bg_start','button_bg_end','button_text_color','button_border_radius','button_style','show_trust_badges','checkout_logo','checkout_primary_color','checkout_accent_color','checkout_font_family','checkout_header_text','checkout_footer_text'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('stores', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};