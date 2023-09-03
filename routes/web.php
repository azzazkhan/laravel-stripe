<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', 'HomeController')->name('home');
Route::view('/dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('checkout')
    ->name('checkout.')
    ->controller('CheckoutController')
    ->middleware('auth')
    ->group(function () {
        Route::post('', 'checkout')->name('initiate');
        Route::get('{transaction:ulid}/success', 'success')->name('success');
        Route::get('{transaction:ulid}/cancel', 'cancel')->name('cancel');
    });

Route::prefix('profile')
    ->controller('ProfileController')
    ->name('profile.')
    ->middleware('auth')
    ->group(function () {
        Route::get('', 'edit')->name('edit');
        Route::patch('', 'update')->name('update');
        Route::delete('', 'destroy')->name('destroy');
    });

require __DIR__ . '/auth.php';
