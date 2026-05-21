<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->string('pricing_region', 20)->default('luzon')->after('is_main');
        });
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn('pricing_region');
        });
    }
};
