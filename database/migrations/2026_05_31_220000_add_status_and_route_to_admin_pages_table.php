<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_pages', function (Blueprint $table) {
            $table->string('status', 20)->default('published')->after('title');
            $table->string('route_name')->nullable()->after('status');
            $table->string('path')->nullable()->after('route_name');
            $table->boolean('is_system')->default(false)->after('path');
        });

        Schema::table('admin_pages', function (Blueprint $table) {
            $table->json('layout_json')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('admin_pages', function (Blueprint $table) {
            $table->dropColumn(['status', 'route_name', 'path', 'is_system']);
        });
    }
};
