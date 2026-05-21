<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OrderAnalyticsController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AdminOperatorProductController;
use App\Http\Controllers\Admin\AdminPosDailyClosingController;
use App\Http\Controllers\Admin\PosSalesLogController;
use App\Http\Controllers\Admin\PurchasingAnalyticsController;
use App\Http\Controllers\Admin\PurchasingProductController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Distributor\DashboardController as DistributorDashboardController;
use App\Http\Controllers\Distributor\DistributorAnalyticsController;
use App\Http\Controllers\Distributor\OrderController as DistributorOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\OperatorAnalyticsController;
use App\Http\Controllers\Operator\OperatorInventoryController;
use App\Http\Controllers\Operator\OperatorPosController;
use App\Http\Controllers\Operator\OperatorPosDailyClosingController;
use App\Http\Controllers\Operator\OperatorProductsForSaleController;
use App\Http\Controllers\Operator\OperatorStorefrontController;
use App\Http\Controllers\Operator\GenealogyController;
use App\Http\Controllers\Operator\OrderController as OperatorOrderController;
use App\Http\Controllers\Operator\ReferralController;
use App\Http\Controllers\Operator\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/store/{operatorId}', [OperatorStorefrontController::class, 'show'])->name('store.menu');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('role:super_admin,purchasing_admin,finance_admin,it_admin')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::middleware('role:purchasing_admin,super_admin')->prefix('purchasing')->name('purchasing.')->group(function () {
            Route::get('/stock-movements', [StockMovementController::class, 'index'])->name('stock-movements.index');
            Route::get('/stock-movements/{stockMovement}', [StockMovementController::class, 'show'])->name('stock-movements.show');
            Route::get('/products', [PurchasingProductController::class, 'index'])->name('products.index');
            Route::get('/products/create', [PurchasingProductController::class, 'create'])->name('products.create');
            Route::post('/products', [PurchasingProductController::class, 'store'])->name('products.store');
            Route::get('/products/{product}/edit', [PurchasingProductController::class, 'edit'])->name('products.edit');
            Route::put('/products/{product}', [PurchasingProductController::class, 'update'])->name('products.update');
            Route::patch('/products/{product}/toggle-status', [PurchasingProductController::class, 'toggleStatus'])->name('products.toggle-status');

            Route::middleware('role:purchasing_admin')->group(function () {
                Route::get('/analytics', [PurchasingAnalyticsController::class, 'index'])->name('analytics');
                Route::get('/products/export', [PurchasingProductController::class, 'export'])->name('products.export');
                Route::get('/products/import-template', [PurchasingProductController::class, 'importTemplate'])->name('products.import-template');
                Route::post('/products/import', [PurchasingProductController::class, 'import'])->name('products.import');
                Route::get('/products/import-report', [PurchasingProductController::class, 'importReport'])->name('products.import-report');
                Route::post('/products/bulk-update', [PurchasingProductController::class, 'bulkUpdate'])->name('products.bulk-update');
                Route::delete('/products/bulk-delete', [PurchasingProductController::class, 'bulkDelete'])->name('products.bulk-delete');
                Route::post('/products/bulk-price-update', [PurchasingProductController::class, 'bulkPriceUpdate'])->name('products.bulk-price-update');
                Route::post('/products/bulk-category-update', [PurchasingProductController::class, 'bulkCategoryUpdate'])->name('products.bulk-category-update');
                Route::post('/products/bulk-inventory-update', [PurchasingProductController::class, 'bulkInventoryUpdate'])->name('products.bulk-inventory-update');
            });
        });

        Route::get('/products', fn () => to_route('admin.purchasing.products.index'));
        Route::get('/products/bulk-edit', fn () => to_route('admin.purchasing.products.index'));
        Route::get('/products/create', fn () => to_route('admin.purchasing.products.create'));
        Route::get('/products/{product}/edit', fn (App\Models\Product $product) => to_route('admin.purchasing.products.edit', $product));

        Route::middleware('role:super_admin,finance_admin,purchasing_admin')->group(function () {
            Route::get('/orders/analytics', [OrderAnalyticsController::class, 'index'])->name('orders.analytics');
        });

        Route::middleware('role:super_admin,purchasing_admin')->group(function () {
            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->name('orders.approve');
            Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->name('orders.reject');
            Route::post('/orders/{order}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');
        });

        Route::middleware('role:super_admin,finance_admin')->group(function () {
            Route::get('/finance/wallets', [FinanceController::class, 'wallets'])->name('finance.wallets');
            Route::get('/finance/rebates', [FinanceController::class, 'rebates'])->name('finance.rebates');
            Route::get('/finance/overrides', [FinanceController::class, 'overrides'])->name('finance.overrides');
            Route::get('/finance/withdrawals', [FinanceController::class, 'withdrawals'])->name('finance.withdrawals');
            Route::get('/finance/reports', [FinanceController::class, 'reports'])->name('finance.reports');
            Route::post('/finance/withdrawals/{withdrawal}/approve', [FinanceController::class, 'approveWithdrawal'])->name('finance.withdrawals.approve');
            Route::post('/finance/withdrawals/{withdrawal}/reject', [FinanceController::class, 'rejectWithdrawal'])->name('finance.withdrawals.reject');
        });

        Route::middleware('role:super_admin')->group(function () {
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');

            Route::get('/operator-products', [AdminOperatorProductController::class, 'index'])->name('operator-products.index');
            Route::put('/operator-products/{operatorProduct}', [AdminOperatorProductController::class, 'update'])->name('operator-products.update');
            Route::get('/pos-sales-logs/secure', [PosSalesLogController::class, 'secure'])->name('pos-sales-logs.secure');
            Route::post('/pos-sales-logs/unlock', [PosSalesLogController::class, 'unlock'])->name('pos-sales-logs.unlock');
            Route::get('/pos-sales-logs/export', [PosSalesLogController::class, 'export'])->name('pos-sales-logs.export');
            Route::get('/pos-sales-logs', [PosSalesLogController::class, 'index'])->name('pos-sales-logs.index');
            Route::get('/pos/daily-closings', [AdminPosDailyClosingController::class, 'index'])->name('pos.daily-closings.index');
            Route::post('/pos/daily-closing/{posDailyClosing}/approve', [AdminPosDailyClosingController::class, 'approve'])->name('pos.daily-closing.approve');
            Route::post('/pos/daily-closing/{posDailyClosing}/reject', [AdminPosDailyClosingController::class, 'reject'])->name('pos.daily-closing.reject');
            Route::post('/pos/daily-closing/{posDailyClosing}/reopen', [AdminPosDailyClosingController::class, 'reopen'])->name('pos.daily-closing.reopen');
        });

        Route::middleware('role:super_admin,it_admin')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('/logs', [SettingsController::class, 'logs'])->name('settings.logs');
        });
    });
});

Route::middleware(['auth', 'role:distributor'])->prefix('distributor')->name('distributor.')->group(function () {
    Route::get('/', DistributorDashboardController::class)->name('dashboard');
    Route::get('/analytics', [DistributorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/orders', [DistributorOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [DistributorOrderController::class, 'createFromMain'])->name('orders.create');
    Route::post('/orders', [DistributorOrderController::class, 'storeFromMain'])->name('orders.store');
    Route::post('/orders/{order}/approve', [DistributorOrderController::class, 'approve'])->name('orders.approve');
});

Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/', OperatorDashboardController::class)->name('dashboard');
    Route::get('/analytics', [OperatorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/pos', [OperatorPosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [OperatorPosController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/daily-closing', [OperatorPosDailyClosingController::class, 'store'])->name('pos.daily-closing.store');
    Route::get('/supplies-inventory', [OperatorInventoryController::class, 'index'])->name('supplies-inventory.index');
    Route::post('/supplies-inventory/adjust', [OperatorInventoryController::class, 'adjust'])->name('supplies-inventory.adjust');
    Route::get('/products-for-sale', [OperatorProductsForSaleController::class, 'index'])->name('products-for-sale.index');
    Route::post('/products-for-sale', [OperatorProductsForSaleController::class, 'store'])->name('products-for-sale.store');
    Route::put('/products-for-sale/{operatorProduct}', [OperatorProductsForSaleController::class, 'update'])->name('products-for-sale.update');
    Route::post('/products-for-sale/{operatorProduct}/toggle', [OperatorProductsForSaleController::class, 'toggle'])->name('products-for-sale.toggle');
    Route::get('/orders', [OperatorOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OperatorOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OperatorOrderController::class, 'store'])->name('orders.store');
    Route::get('/referrals/create', [ReferralController::class, 'create'])->name('referrals.create');
    Route::post('/referrals', [ReferralController::class, 'store'])->name('referrals.store');
    Route::get('/genealogy', GenealogyController::class)->name('genealogy');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/rebates', [WalletController::class, 'rebates'])->name('rebates');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
});
