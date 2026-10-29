<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\TeamController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Só para quem NÃO está logado.
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [RegisterController::class, 'create'])->name('register');
    Route::post('/cadastro', [RegisterController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/entrar', [LoginController::class, 'create'])->name('login');
    Route::post('/entrar', [LoginController::class, 'store']);
});

// Só para quem está logado.
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => view('home'))->name('home');
    Route::post('/sair', [LoginController::class, 'destroy'])->name('logout');

    // Clientes: qualquer membro da loja (o delete é restrito pela policy).
    Route::prefix('clientes')->name('customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::get('/novo', [CustomerController::class, 'create'])->name('create');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/{customer}', [CustomerController::class, 'show'])->name('show')->whereNumber('customer');
        Route::get('/{customer}/editar', [CustomerController::class, 'edit'])->name('edit')->whereNumber('customer');
        Route::put('/{customer}', [CustomerController::class, 'update'])->name('update')->whereNumber('customer');
        Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy')->whereNumber('customer');
    });

    // Aparelhos: qualquer membro da loja (o delete é restrito pela policy).
    Route::prefix('aparelhos')->name('devices.')->group(function () {
        Route::get('/', [DeviceController::class, 'index'])->name('index');
        Route::get('/novo', [DeviceController::class, 'create'])->name('create');
        Route::post('/', [DeviceController::class, 'store'])->name('store');
        Route::get('/{device}/editar', [DeviceController::class, 'edit'])->name('edit')->whereNumber('device');
        Route::put('/{device}', [DeviceController::class, 'update'])->name('update')->whereNumber('device');
        Route::delete('/{device}', [DeviceController::class, 'destroy'])->name('destroy')->whereNumber('device');
    });

    // Área do dono: o middleware `can:` executa UserPolicy::viewAny antes do controller.
    Route::middleware('can:viewAny,'.User::class)
        ->prefix('equipe')
        ->name('team.')
        ->group(function () {
            Route::get('/', [TeamController::class, 'index'])->name('index');
            Route::get('/novo', [TeamController::class, 'create'])->name('create');
            Route::post('/', [TeamController::class, 'store'])->name('store');
            Route::get('/{member}/editar', [TeamController::class, 'edit'])->name('edit')->whereNumber('member');
            Route::put('/{member}', [TeamController::class, 'update'])->name('update')->whereNumber('member');
            Route::delete('/{member}', [TeamController::class, 'destroy'])->name('destroy')->whereNumber('member');
        });
});