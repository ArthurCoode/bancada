<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
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