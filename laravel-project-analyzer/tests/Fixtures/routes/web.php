<?php

use Illuminate\Support\Facades\Route;

Route::get('/users', [App\Http\Controllers\UserController::class, 'index'])->name('users.index');
Route::post('/users', [App\Http\Controllers\UserController::class, 'store'])->name('users.store');
Route::get('/users/{user}', [App\Http\Controllers\UserController::class, 'show'])->name('users.show');
