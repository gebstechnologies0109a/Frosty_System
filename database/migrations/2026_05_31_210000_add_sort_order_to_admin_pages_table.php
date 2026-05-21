<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_pages', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('title');
        });

        $position = 0;
        foreach (DB::table('admin_pages')->orderBy('id')->pluck('id') as $id) {
            DB::table('admin_pages')->where('id', $id)->update(['sort_order' => ++$position]);
        }
    }

    public function down(): void
    {
        Schema::table('admin_pages', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
