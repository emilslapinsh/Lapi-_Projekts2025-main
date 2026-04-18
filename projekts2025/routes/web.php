<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\MapController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/home', function () {
        return view('dashboard.home');
    })->name('home');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/karte', function () {
        return view('dashboard.karte');
    })->name('karte');

    Route::get('/calendar', function () {
        return view('dashboard.calendar');
    })->name('calendar');

    Route::get('/profils', function () {
        return view('dashboard.profile');
    })->name('profile');


    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */

    Route::get('/admin', [AdminController::class, 'index'])->name('admin');
    Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.deleteUser');


    /*
    |--------------------------------------------------------------------------
    | Map API
    |--------------------------------------------------------------------------
    */

    Route::get('/api/locations', [MapController::class, 'fetchLocations']);


    /*
    |--------------------------------------------------------------------------
    | Calendar API
    |--------------------------------------------------------------------------
    */

    Route::get('/api/events', [EventController::class, 'fetchEvents']);
    Route::post('/api/events', [EventController::class, 'store']);


    /*
    |--------------------------------------------------------------------------
    | Izdevumi (Auto + Ieraksti)
    |--------------------------------------------------------------------------
    */

    // Cars
    Route::get('/izdevumi', [CarController::class, 'index'])->name('izdevumi.index');
    Route::post('/izdevumi/add-car', [CarController::class, 'store'])->name('izdevumi.store');

    Route::post('/cars/{car}/share', [CarController::class, 'share'])->name('cars.share');
    Route::post('/cars/{car}/confirm', [CarController::class, 'confirmShare'])->name('cars.confirm');

    // Expenses
    Route::post('/izdevumi/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::delete('/izdevumi/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/izdevumi/export', [ExpenseController::class, 'export'])->name('expenses.export');


    /*
    |--------------------------------------------------------------------------
    | Degvielas patēriņš
    |--------------------------------------------------------------------------
    */

    Route::get('/degviela', [FuelController::class, 'index'])->name('degviela.index');
    Route::post('/degviela', [FuelController::class, 'store'])->name('degviela.store');
    Route::delete('/degviela/{fuel}', [FuelController::class, 'destroy'])->name('degviela.destroy');
    Route::get('/degviela/export', [FuelController::class, 'export'])->name('degviela.export');
});
