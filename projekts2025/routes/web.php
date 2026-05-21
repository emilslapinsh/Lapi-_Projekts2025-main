<?php

// Vietnes galvenās HTTP maršrutu definīcijas
// Publiskā zona: sveiciena lapa un autentifikācijas formas
// Aizsargātā zona: dashboard, kartes/kalendāra skati, vietas un notikumi, izdevumi un degviela

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\MapController;
use App\Support\EventCalendarTypes;
use Illuminate\Support\Facades\Route;

// Publiski: welcome, pieslēgšanās un reģistrācija

Route::redirect('/', '/welcome');

// Welcome lapa
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

// Jauns lietotājs un sesijas uzsākšana
Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

// Lietoti tikai pieslēgtiem lietotājiem

Route::middleware(['auth'])->group(function () {

    // Panelis kā noklusējuma ieeja pēc pieslēgšanās
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Kartes pilnekrāna skats 
    Route::redirect('/karte', '/map');

    Route::get('/map', function () {
        return view('dashboard.map');
    })->name('karte');

    // Kalendārs saņem atļautos notikumu tipus kā sarakstu frontendam
    Route::get('/calendar', function () {
        return view('dashboard.calendar', [
            'eventTypes' => EventCalendarTypes::TYPES,
        ]);
    })->name('calendar');

    Route::get('/profils', function () {
        return view('dashboard.profile');
    })->name('profile');

    // Tikai administratoriem (lietotāju pārvalde)

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin');
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.deleteUser');
    });

    // lokācijas pēc kartes bbox un zoom tipa filtros

    Route::get('/api/locations', [MapController::class, 'fetchLocations']);

    // kalendāra notikumu atlase un izmaiņas

    Route::get('/api/events', [EventController::class, 'fetchEvents']);
    Route::post('/api/events', [EventController::class, 'store']);
    Route::put('/api/events/{event}', [EventController::class, 'update'])->name('api.events.update');
    Route::delete('/api/events/{event}', [EventController::class, 'destroy'])->name('api.events.destroy');

    // Automašīnas saraksts koplietošanā un uz izdevumu sadaļu

    Route::redirect('/izdevumi', '/expenses');

    Route::get('/expenses', [CarController::class, 'index'])->name('izdevumi.index');
    Route::post('/expenses/add-car', [CarController::class, 'store'])->name('izdevumi.store');

    Route::post('/cars/{car}/share', [CarController::class, 'share'])->name('cars.share');
    Route::post('/cars/{car}/confirm', [CarController::class, 'confirmShare'])->name('cars.confirm');
    Route::delete('/cars/{car}', [CarController::class, 'destroy'])->name('cars.destroy');

    // Izdevumu ieraksti un eksports 
    Route::post('/expenses/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('/expenses/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/expenses/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');

    Route::redirect('/izdevumi/export', '/expenses/export');
    Route::redirect('/izdevumi/expenses', '/expenses');
    Route::redirect('/izdevumi/expenses/', '/expenses');

    // Degvielas uzpildes pieraksti un eksports

    Route::redirect('/degviela', '/fuel');

    Route::get('/fuel', [FuelController::class, 'index'])->name('degviela.index');
    Route::post('/fuel', [FuelController::class, 'store'])->name('degviela.store');
    Route::delete('/fuel/{fuel}', [FuelController::class, 'destroy'])->name('degviela.destroy');
    Route::get('/fuel/export', [FuelController::class, 'export'])->name('degviela.export');

    Route::redirect('/degviela/export', '/fuel/export');
});
