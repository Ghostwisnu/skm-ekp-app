<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DailyCostController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NavigationMenuController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:admin,operator,keuangan')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    });

    Route::middleware('role:admin,operator')->group(function () {
        Route::resource('trips', TripController::class)->except('show');
        Route::resource('daily-costs', DailyCostController::class)->only(['index', 'store', 'update', 'destroy'])
            ->parameters(['daily-costs' => 'dailyCost']);
        Route::resource('clients', ClientController::class)->only(['store', 'update', 'destroy']);
        Route::resource('vehicles', VehicleController::class)->only(['store', 'update', 'destroy']);
    });

    Route::middleware('role:admin,keuangan')->group(function () {
        Route::resource('invoices', InvoiceController::class)->except(['edit', 'update']);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('invoices/{invoice}/excel', [InvoiceController::class, 'excel'])->name('invoices.excel');
        Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');

        Route::get('reports/vehicles', [ReportController::class, 'vehicles'])->name('reports.vehicles');
        Route::get('reports/vehicles/pdf', [ReportController::class, 'pdf'])->name('reports.vehicles.pdf');
        Route::get('reports/vehicles/excel', [ReportController::class, 'excel'])->name('reports.vehicles.excel');
    });

    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('settings', [SettingController::class, 'show'])->name('settings.show');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');

        // Navigation Menu Management
        Route::get('navigation-menus', [NavigationMenuController::class, 'index'])->name('navigation.menus.index');
        Route::post('navigation-menus', [NavigationMenuController::class, 'store'])->name('navigation.menus.store');
        Route::put('navigation-menus/{id}', [NavigationMenuController::class, 'update'])->name('navigation.menus.update');
        Route::delete('navigation-menus/{id}', [NavigationMenuController::class, 'destroy'])->name('navigation.menus.destroy');
        Route::post('navigation-menus/seed-defaults', [NavigationMenuController::class, 'seedDefaults'])->name('navigation.menus.seed-defaults');
    });
});

require __DIR__.'/settings.php';
