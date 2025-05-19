<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;


// 仮のルート (ヘッダーのリンク用)
Route::get('/attendances/list', function () { return "勤怠一覧 (未実装)"; })->middleware(['auth', 'verified'])->name('attendances.list');
Route::get('/applications', function () { return "申請 (未実装)"; })->middleware(['auth', 'verified'])->name('applications.index');

// 会員登録画面表示
Route::get('/register', [RegisteredUserController::class, 'create'])
    ->middleware('guest') // 未ログインユーザーのみアクセス可能
    ->name('register');

// 会員登録処理
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest');

// ログイン画面表示
Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login'); // Fortifyがこの名前を期待する

// ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');

// ログアウト処理 (参考: 通常ヘッダーなどに配置)
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

    // 勤怠登録画面 (ログイン・メール認証済みユーザーのみ)
Route::get('/attendances', [AttendanceController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('attendances.index');

// 出勤処理
Route::post('/attendances/clock-in', [AttendanceController::class, 'clockIn'])
    ->middleware(['auth', 'verified'])
    ->name('attendances.clockin');

    // 退勤処理
Route::post('/attendances/clock-out', [AttendanceController::class, 'clockOut'])
    ->middleware(['auth', 'verified'])
    ->name('attendances.clockout');

// 休憩開始処理
Route::post('/attendances/break/start', [AttendanceController::class, 'startBreak'])
    ->middleware(['auth', 'verified'])
    ->name('attendances.break.start');

    // 休憩終了処理
Route::post('/attendances/break/end', [AttendanceController::class, 'endBreak'])
    ->middleware(['auth', 'verified'])
    ->name('attendances.break.end');