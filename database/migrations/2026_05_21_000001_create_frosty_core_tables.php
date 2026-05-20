<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('member_code')->unique();
            $table->string('email')->nullable();
            $table->foreignId('referrer_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('kilo_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('kilos', 10, 2);
            $table->decimal('direct_points', 10, 2);
            $table->timestamp('purchased_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'purchased_at']);
        });

        Schema::create('point_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);
            $table->decimal('points', 10, 2);
            $table->decimal('kilos_basis', 10, 2);
            $table->foreignId('source_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('kilo_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->timestamps();

            $table->index(['member_id', 'period_year', 'period_month']);
        });

        Schema::create('monthly_member_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('total_kilos', 10, 2)->default(0);
            $table->decimal('total_direct_points', 10, 2)->default(0);
            $table->decimal('total_override_points', 10, 2)->default(0);
            $table->boolean('override_qualified')->default(false);
            $table->timestamps();

            $table->unique(['member_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_member_summaries');
        Schema::dropIfExists('point_ledger');
        Schema::dropIfExists('kilo_purchases');
        Schema::dropIfExists('members');
        Schema::dropIfExists('stores');
    }
};
