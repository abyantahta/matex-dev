<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 4)->nullable()->after('qty_confirmed');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->text('rm_price_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('rm_price_notes');
        });
    }
};
