<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CommentController;


Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', [ItemController::class, 'index'])->name('top');
Route::get('/item/{id}', [ItemController::class, 'show'])->name('item_show');

Route::middleware('auth')->group(function () {
    Route::post('/favorite/{id}', [FavoriteController::class, 'toggle'])->name('favorite.toggle');
});

Route::post('/comment/{product}', [CommentController::class, 'store'])
    ->middleware('auth')
    ->name('comment.store');

Route::get('/purchase/{item_id}', [PurchaseController::class, 'show'])
    ->middleware('auth')
    ->name('purchase.show');
Route::post('/purchase/{item_id}', [PurchaseController::class, 'store'])->name('purchase.store');

Route::get('/purchase/address/{item_id}', [PurchaseController::class, 'editAddress'])->name('purchase.address.edit');
Route::patch('/purchase/address/{item_id}', [PurchaseController::class, 'updateAddress'])->name('purchase.address.update');

Route::get('/mypage', [ProfileController::class, 'mypage'])
    ->middleware('auth')
    ->name('mypage');

Route::middleware(['auth'])->group(function () {
    Route::get('/mypage/profile', [ProfileController::class, 'show'])->name('mypage.profile');
    Route::post('/mypage/profile/update', [ProfileController::class, 'update'])->name('mypage.profile.update');
});

Route::get('/sell', [ItemController::class, 'create'])
    ->middleware('auth')
    ->name('sell');
Route::post('/sell', [ItemController::class, 'store'])->name('sell.store');