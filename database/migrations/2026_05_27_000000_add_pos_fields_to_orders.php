<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_type', 16)->default('supply')->after('source');
            $table->foreignId('operator_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->decimal('cogs_total', 12, 2)->nullable()->after('total_amount');
            $table->decimal('gross_profit', 12, 2)->nullable()->after('cogs_total');
            $table->timestamp('completed_at')->nullable()->after('approved_at');

            $table->index(['order_type', 'operator_id', 'created_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('price');
            $table->decimal('line_total', 12, 2)->nullable()->after('cost_price');
        });

        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_logs');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'line_total']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['operator_id']);
            $table->dropColumn(['order_type', 'operator_id', 'cogs_total', 'gross_profit', 'completed_at']);
        });
    }
};
