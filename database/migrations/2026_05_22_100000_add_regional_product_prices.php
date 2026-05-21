<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_luzon', 12, 2)->nullable()->after('category');
            $table->decimal('price_davao', 12, 2)->nullable()->after('price_luzon');
            $table->decimal('price_tacloban', 12, 2)->nullable()->after('price_davao');
        });

        if (Schema::hasColumn('products', 'price')) {
            DB::table('products')->update([
                'price_luzon' => DB::raw('price'),
                'price_davao' => DB::raw('price'),
                'price_tacloban' => DB::raw('price'),
            ]);

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('region', 20)->default('luzon')->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('price_region', 20)->default('luzon')->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price', 12, 2)->default(0)->after('category');
        });

        DB::table('products')->update(['price' => DB::raw('price_luzon')]);

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_luzon', 'price_davao', 'price_tacloban']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('region');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('price_region');
        });
    }
};
