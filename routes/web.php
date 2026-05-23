<?php

use App\Enums\UserRole;
use App\Http\Controllers\Admin\AdminDistributorController;
use App\Http\Controllers\Admin\AdminOperatorController;
use App\Http\Controllers\Admin\AdminPendingOrderController;
use App\Http\Controllers\Admin\AdminPendingWithdrawalController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OrderAnalyticsController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\AdminOperatorProductController;
use App\Http\Controllers\Admin\AdminPosDailyClosingController;
use App\Http\Controllers\Admin\PosSalesLogController;
use App\Http\Controllers\Admin\PurchasingAnalyticsController;
use App\Http\Controllers\Admin\PurchasingProductController;
use App\Http\Controllers\Admin\AdminStockLogController;
use App\Http\Controllers\Admin\StockMovementController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\AdminPageBuilderController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AdminPageController;
use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Distributor\DistributorDashboardController;
use App\Http\Controllers\Distributor\DistributorInventoryController;
use App\Http\Controllers\Distributor\DistributorAnalyticsController;
use App\Http\Controllers\Distributor\OrderController as DistributorOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\OperatorAnalyticsController;
use App\Http\Controllers\Operator\OperatorInventoryController;
use App\Http\Controllers\Operator\OperatorPosController;
use App\Http\Controllers\Operator\OperatorProductController;
use App\Http\Controllers\Operator\OperatorProductsForSaleController;
use App\Http\Controllers\Operator\OperatorStorefrontController;
use App\Http\Controllers\Operator\GenealogyController;
use App\Http\Controllers\Operator\OrderController as OperatorOrderController;
use App\Http\Controllers\Operator\ReferralController;
use App\Http\Controllers\Operator\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/store/{operatorId}', [OperatorStorefrontController::class, 'show'])->name('store.menu');
Route::get('/p/{slug}', [AdminPageController::class, 'show'])->name('pages.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::bind('operator', fn (string $value) => User::query()->where('role', UserRole::Operator)->findOrFail($value));
Route::bind('distributor', fn (string $value) => User::query()->where('role', UserRole::Distributor)->findOrFail($value));

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/users/stop-impersonate', [AdminUserController::class, 'stopImpersonate'])->name('users.stop-impersonate');

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

            Route::middleware('role:purchasing_admin,super_admin')->group(function () {
                Route::post('/stock-logs/{stockLog}/approve', [AdminStockLogController::class, 'approve'])->name('stock-logs.approve');
                Route::post('/stock-logs/{stockLog}/reject', [AdminStockLogController::class, 'reject'])->name('stock-logs.reject');
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

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::get('/products/bulk-edit', fn () => to_route('admin.purchasing.products.index'));
        Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('products.show');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::patch('/products/{product}/toggle-status', [AdminProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::get('/products/{product}/stock-logs', [AdminProductController::class, 'stockLogs'])->name('products.stock-logs');
        Route::get('/products/{product}/price-history', [AdminProductController::class, 'priceHistory'])->name('products.price-history');

        Route::middleware('role:super_admin,finance_admin,purchasing_admin')->group(function () {
            Route::get('/orders/analytics', [OrderAnalyticsController::class, 'index'])->name('orders.analytics');
        });

        Route::middleware('role:super_admin,purchasing_admin')->group(function () {
            Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
            Route::get('/orders/{order}/payment-proof', [AdminOrderController::class, 'downloadPaymentProof'])->whereNumber('order')->name('orders.payment-proof');
            Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->whereNumber('order')->name('orders.update-status');
            Route::post('/orders/{order}/approve', [AdminOrderController::class, 'approve'])->whereNumber('order')->name('orders.approve');
            Route::post('/orders/{order}/reject', [AdminOrderController::class, 'reject'])->whereNumber('order')->name('orders.reject');
            Route::post('/orders/{order}/complete', [AdminOrderController::class, 'complete'])->whereNumber('order')->name('orders.complete');
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
            Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
            Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
            Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
            Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
            Route::patch('/users/{user}/role', [AdminUserController::class, 'changeRole'])->name('users.change-role');
            Route::patch('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::post('/users/{user}/force-logout', [AdminUserController::class, 'forceLogout'])->name('users.force-logout');
            Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
            Route::get('/users/{user}/related', [AdminUserController::class, 'relatedData'])->name('users.related');

            Route::get('/page-builder', [AdminPageBuilderController::class, 'index'])->name('page-builder.index');
            Route::get('/page-builder/manage', [AdminPageBuilderController::class, 'manage'])->name('page-builder.manage');
            Route::get('/page-builder/pages', [AdminPageBuilderController::class, 'listPages'])->name('page-builder.pages');
            Route::post('/page-builder/pages', [AdminPageBuilderController::class, 'storePage'])->name('page-builder.pages.store');
            Route::get('/page-builder/pages/{page}', [AdminPageBuilderController::class, 'showPage'])->name('page-builder.pages.show');
            Route::put('/page-builder/pages/{page}', [AdminPageBuilderController::class, 'updatePage'])->name('page-builder.pages.update');
            Route::post('/page-builder/pages/{page}/publish', [AdminPageBuilderController::class, 'publishPage'])->name('page-builder.pages.publish');
            Route::post('/page-builder/pages/{page}/template', [AdminPageBuilderController::class, 'applyTemplate'])->name('page-builder.pages.template');
            Route::delete('/page-builder/pages/{page}', [AdminPageBuilderController::class, 'destroyPage'])->name('page-builder.pages.destroy');
            Route::get('/page-preview/{page}', [AdminPageBuilderController::class, 'preview'])->name('page-preview');
            Route::post('/page-builder/sync', [AdminPageBuilderController::class, 'sync'])->name('page-builder.sync');
            Route::post('/page-builder/reorder', [AdminPageBuilderController::class, 'reorder'])->name('page-builder.reorder');
            Route::get('/page-builder/create', [AdminPageBuilderController::class, 'create'])->name('page-builder.create');
            Route::post('/page-builder', [AdminPageBuilderController::class, 'store'])->name('page-builder.store');
            Route::get('/page-builder/{page}/edit', [AdminPageBuilderController::class, 'edit'])->name('page-builder.edit');
            Route::put('/page-builder/{page}', [AdminPageBuilderController::class, 'update'])->name('page-builder.update');
            Route::post('/page-builder/{page}/duplicate', [AdminPageBuilderController::class, 'duplicate'])->name('page-builder.duplicate');
            Route::patch('/page-builder/{page}/toggle-status', [AdminPageBuilderController::class, 'toggleStatus'])->name('page-builder.toggle-status');
            Route::delete('/page-builder/{page}', [AdminPageBuilderController::class, 'destroy'])->name('page-builder.destroy');
            Route::get('/page-builder/{page}/preview', [AdminPageBuilderController::class, 'preview'])->name('page-builder.preview');

            Route::redirect('/pos/logs', '/admin/pos-sales-logs')->name('pos.logs');
            Route::redirect('/pos/closings', '/admin/pos/daily-closings')->name('pos.closings');

            Route::resource('operators', AdminOperatorController::class);
            Route::patch('/operators/{operator}/toggle-status', [AdminOperatorController::class, 'toggleStatus'])->name('operators.toggle-status');
            Route::post('/operators/{operator}/reset-password', [AdminOperatorController::class, 'resetPassword'])->name('operators.reset-password');
            Route::get('/operators/{operator}/inventory', [AdminOperatorController::class, 'inventory'])->name('operators.inventory');
            Route::get('/operators/{operator}/store-menu', [AdminOperatorController::class, 'storeMenu'])->name('operators.store-menu');
            Route::get('/operators/{operator}/pos-logs', [AdminOperatorController::class, 'posLogs'])->name('operators.pos-logs');
            Route::get('/operators/{operator}/daily-closings', [AdminOperatorController::class, 'dailyClosings'])->name('operators.daily-closings');

            Route::resource('distributors', AdminDistributorController::class);
            Route::patch('/distributors/{distributor}/toggle-status', [AdminDistributorController::class, 'toggleStatus'])->name('distributors.toggle-status');
            Route::post('/distributors/{distributor}/reset-password', [AdminDistributorController::class, 'resetPassword'])->name('distributors.reset-password');
            Route::get('/distributors/{distributor}/orders', [AdminDistributorController::class, 'orders'])->name('distributors.orders');

            Route::get('/orders/pending', [AdminPendingOrderController::class, 'index'])->name('orders.pending');
            Route::get('/orders/pending/{order}', [AdminPendingOrderController::class, 'show'])->name('orders.pending.show');
            Route::post('/orders/pending/{order}/approve', [AdminPendingOrderController::class, 'approve'])->name('orders.pending.approve');
            Route::post('/orders/pending/{order}/reject', [AdminPendingOrderController::class, 'reject'])->name('orders.pending.reject');
            Route::patch('/orders/pending/{order}/status', [AdminPendingOrderController::class, 'updateStatus'])->name('orders.pending.update-status');

            Route::get('/withdrawals/pending', [AdminPendingWithdrawalController::class, 'index'])->name('withdrawals.pending');
            Route::get('/withdrawals/pending/{withdrawal}', [AdminPendingWithdrawalController::class, 'show'])->name('withdrawals.pending.show');
            Route::post('/withdrawals/pending/{withdrawal}/approve', [AdminPendingWithdrawalController::class, 'approve'])->name('withdrawals.pending.approve');
            Route::post('/withdrawals/pending/{withdrawal}/reject', [AdminPendingWithdrawalController::class, 'reject'])->name('withdrawals.pending.reject');

            Route::get('/operator-products', [AdminOperatorProductController::class, 'index'])->name('operator-products.index');
            Route::put('/operator-products/{operatorProduct}', [AdminOperatorProductController::class, 'update'])->name('operator-products.update');
            Route::redirect('/pos-sales-logs/secure', '/admin/pos-sales-logs')->name('pos-sales-logs.secure');
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
    Route::get('/', [DistributorDashboardController::class, 'index'])->name('dashboard');
    Route::get('/analytics', [DistributorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/orders', [DistributorOrderController::class, 'index'])->name('orders.index');
    Route::get('/inventory', [DistributorInventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/adjust', [DistributorInventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::post('/inventory/adjust', [DistributorInventoryController::class, 'storeAdjustment'])->name('inventory.adjust.store');
    Route::get('/orders/create', [DistributorOrderController::class, 'createFromMain'])->name('orders.create');
    Route::post('/orders', [DistributorOrderController::class, 'storeFromMain'])->name('orders.store');
    Route::post('/orders/{order}/approve', [DistributorOrderController::class, 'approve'])->name('orders.approve');
});

Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/', OperatorDashboardController::class)->name('dashboard');
    Route::get('/analytics', [OperatorAnalyticsController::class, 'index'])->name('analytics');
    Route::get('/pos', [OperatorPosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [OperatorPosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/supplies-inventory', [OperatorInventoryController::class, 'index'])->name('supplies-inventory.index');
    Route::post('/supplies-inventory/adjust', [OperatorInventoryController::class, 'adjust'])->name('supplies-inventory.adjust');
    Route::get('/products-for-sale', [OperatorProductsForSaleController::class, 'index'])->name('products-for-sale.index');
    Route::post('/products-for-sale', [OperatorProductsForSaleController::class, 'store'])->name('products-for-sale.store');
    Route::put('/products-for-sale/{operatorProduct}', [OperatorProductsForSaleController::class, 'update'])->name('products-for-sale.update');
    Route::post('/products-for-sale/{operatorProduct}/toggle', [OperatorProductsForSaleController::class, 'toggle'])->name('products-for-sale.toggle');
    Route::get('/products/search', [OperatorProductController::class, 'search'])->name('products.search');
    Route::get('/orders', [OperatorOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OperatorOrderController::class, 'create'])->name('orders.create');
    Route::post('/orders', [OperatorOrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OperatorOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
    Route::get('/orders/{order}/edit', [OperatorOrderController::class, 'edit'])->whereNumber('order')->name('orders.edit');
    Route::match(['put', 'post'], '/orders/{order}', [OperatorOrderController::class, 'update'])->whereNumber('order')->name('orders.update');
    Route::post('/orders/{order}/payment-proof', [OperatorOrderController::class, 'uploadPaymentProof'])->whereNumber('order')->name('orders.payment-proof');
    Route::post('/orders/{order}/resubmit', [OperatorOrderController::class, 'resubmit'])->whereNumber('order')->name('orders.resubmit');
    Route::get('/referrals/create', [ReferralController::class, 'create'])->name('referrals.create');
    Route::post('/referrals', [ReferralController::class, 'store'])->name('referrals.store');
    Route::get('/genealogy', GenealogyController::class)->name('genealogy');
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
    Route::get('/rebates', [WalletController::class, 'rebates'])->name('rebates');
    Route::post('/wallet/withdraw', [WalletController::class, 'withdraw'])->name('wallet.withdraw');
});
