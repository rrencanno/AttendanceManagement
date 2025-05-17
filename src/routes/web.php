<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;


// 会員登録画面表示
Route::get('/register', [RegisteredUserController::class, 'create'])
    ->middleware('guest') // 未ログインユーザーのみアクセス可能
    ->name('register');

// 会員登録処理
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');