<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Wholesale price and the quantity at which the POS starts charging it.
            // Both nullable so existing products stay retail-only until configured.
            $table->decimal('bulk_price', 12, 2)->nullable()->after('price');
            $table->integer('bulk_min_qty')->nullable()->after('bulk_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['bulk_price', 'bulk_min_qty']);
        });
    }
};
