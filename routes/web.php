<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\OpeningBalanceController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccessoryController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// ============================================
// AUTHENTIFICATION
// ============================================
// 'guest' : redirige automatiquement vers le dashboard si déjà connecté
// (empêche d'atterrir sur /login via le bouton précédent une fois authentifié).
// 'nocache' : interdit au navigateur de mettre cette page en cache (bfcache).
Route::middleware(['guest', 'nocache'])->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    // Limite les essais de mot de passe : 5 tentatives par minute et par adresse IP.
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
});
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ============================================
// ROUTES PROTÉGÉES
// ============================================
Route::middleware(['auth', 'active', 'nocache'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ============================================
    // ADMINISTRATION (Directeur uniquement)
    // ============================================
    Route::middleware(['role:directeur'])->prefix('admin')->name('admin.')->group(function () {
        // Gestion des utilisateurs
        Route::resource('users', UserController::class)->except(['show', 'create', 'edit', 'destroy']);
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        // Soldes d'ouverture
        Route::post('/opening-balances', [\App\Http\Controllers\Admin\OpeningBalanceController::class, 'store'])
        ->name('opening-balances.store');

        // Journal d'audit
        Route::get('/audit', [AuditController::class, 'index'])->name('audit');
        Route::get('/audit/pdf', [AuditController::class, 'pdf'])->name('audit.pdf');

    });

    // ============================================
    // TRANSACTIONS
    // ============================================
    // Création / modification en modale sur la page index : seules ces 3 routes existent.
    Route::resource('transactions', TransactionController::class)->only(['index', 'store', 'update']);
    Route::post('/transactions/{transaction}/cancel', [TransactionController::class, 'cancel'])->name('transactions.cancel');

    // ============================================
    // ACCESSOIRES
    // ============================================
    Route::get('/accessories/catalog', [AccessoryController::class, 'catalog'])->name('accessories.catalog');
    Route::put('/accessory-sales/{sale}', [AccessoryController::class, 'updateSale'])->name('accessory-sales.update');

    Route::resource('accessories', AccessoryController::class)->only(['index', 'store', 'update']);
    Route::post('/accessories/{accessory}/toggle', [AccessoryController::class, 'toggle'])->name('accessories.toggle');
    Route::post('/accessories/sell', [AccessoryController::class, 'sell'])->name('accessories.sell');
    Route::post('/accessories/{accessory}/adjust-stock', [AccessoryController::class, 'adjustStock'])->name('accessories.adjust-stock');

    // ============================================
    // DETTES (Directeur uniquement)
    // ============================================
    Route::middleware(['role:directeur'])->group(function () {
        Route::resource('debts', DebtController::class)->only(['index', 'store', 'update']);
        Route::post('/debts/{debt}/pay', [DebtController::class, 'pay'])->name('debts.pay');
        Route::post('/debts/{debt}/abandon', [DebtController::class, 'abandon'])->name('debts.abandon');
        Route::post('/debts/{debt}/cancel', [DebtController::class, 'cancel'])->name('debts.cancel');
        Route::delete('/payments/{id}', [DebtController::class, 'cancelPayment'])->name('debts.cancel-payment');
    });

    // ============================================
    // RAPPORTS PDF (Directeur uniquement)
    // ============================================
    Route::middleware(['role:directeur'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/daily', [ReportController::class, 'daily'])->name('daily');
        Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('/debts', [ReportController::class, 'debts'])->name('debts');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/client', [ReportController::class, 'clientStatement'])->name('client');
});
});
