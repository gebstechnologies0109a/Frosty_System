<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('region', 20);
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['product_id', 'region']);
            $table->index('region');
        });

        if (Schema::hasColumn('products', 'price_luzon')) {
            $regions = ['luzon', 'davao', 'tacloban'];
            $now = now();

            foreach (DB::table('products')->get() as $product) {
                foreach ($regions as $region) {
                    $column = 'price_'.$region;
                    $price = $product->{$column} ?? $product->price_luzon ?? 0;

                    DB::table('product_prices')->insert([
                        'product_id' => $product->id,
                        'region' => $region,
                        'price' => $price,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['price_luzon', 'price_davao', 'price_tacloban']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('price_luzon', 12, 2)->nullable()->after('category');
            $table->decimal('price_davao', 12, 2)->nullable()->after('price_luzon');
            $table->decimal('price_tacloban', 12, 2)->nullable()->after('price_davao');
        });

        foreach (DB::table('products')->get() as $product) {
            $prices = DB::table('product_prices')
                ->where('product_id', $product->id)
                ->pluck('price', 'region');

            DB::table('products')->where('id', $product->id)->update([
                'price_luzon' => $prices['luzon'] ?? 0,
                'price_davao' => $prices['davao'] ?? 0,
                'price_tacloban' => $prices['tacloban'] ?? 0,
            ]);
        }

        Schema::dropIfExists('product_prices');
    }
};
