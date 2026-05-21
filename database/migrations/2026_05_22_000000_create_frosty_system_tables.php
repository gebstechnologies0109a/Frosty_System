<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('points_ledger');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('distributors');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropForeign(['sponsor_id']);
                $table->dropForeign(['distributor_id']);
                $table->dropColumn(['role', 'sponsor_id', 'genealogy_level', 'genealogy_path', 'distributor_id', 'status']);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('password');
            $table->foreignId('sponsor_id')->nullable()->after('role')->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('genealogy_level')->default(0)->after('sponsor_id');
            $table->string('genealogy_path', 500)->nullable()->after('genealogy_level');
            $table->unsignedBigInteger('distributor_id')->nullable()->after('genealogy_path');
            $table->string('status')->default('active')->after('distributor_id');
        });

        Schema::create('distributors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_main')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('is_main');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('distributor_id')->references('id')->on('distributors')->nullOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('points')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['category', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('distributor_id')->constrained('distributors')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->unsignedInteger('total_points')->default(0);
            $table->string('source');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['distributor_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('points');
            $table->timestamps();
        });

        Schema::create('points_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('level')->default(0);
            $table->unsignedInteger('points')->default(0);
            $table->decimal('pesos', 12, 2)->default(0);
            $table->string('type');
            $table->char('month', 7);
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'month']);
            $table->unique(['order_id', 'user_id', 'type', 'level'], 'ledger_order_user_type_level');
        });

        Schema::create('qualifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->unsignedInteger('personal_points')->default(0);
            $table->boolean('qualified')->default(false);
            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'month']);
        });

        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('reference_type', 32);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('qualifications');
        Schema::dropIfExists('points_ledger');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['distributor_id']);
            $table->dropForeign(['sponsor_id']);
            $table->dropColumn(['role', 'sponsor_id', 'genealogy_level', 'genealogy_path', 'distributor_id', 'status']);
        });
        Schema::dropIfExists('distributors');
    }
};
