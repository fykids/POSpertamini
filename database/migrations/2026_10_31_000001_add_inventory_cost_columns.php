<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('buying_price_per_liter', 12, 2)->default(0)->after('price_per_liter');
        });

        Schema::table('stock_refills', function (Blueprint $table): void {
            $table->decimal('total_cost', 14, 2)->default(0)->after('buying_price_per_liter');
        });
    }

    public function down(): void
    {
        Schema::table('stock_refills', fn (Blueprint $table) => $table->dropColumn('total_cost'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('buying_price_per_liter'));
    }
};
