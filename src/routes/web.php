<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\UserCorrectionRequestController;
use App\Http\Controllers\Admin\Auth\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\CorrectionRequestController as AdminCorrectionRequestController;

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

Route::middleware('auth')->group(function () {
    // 勤怠一覧ページ (新規)
    Route::get('/attendances/list', [AttendanceController::class, 'list'])->name('attendances.list');
    // 勤怠詳細ページ (新規 - IDで指定)
    Route::get('/attendances/{attendance}', [AttendanceController::class, 'show'])->name('attendances.show'); // {id} ではなく {attendance} でルートモデルバインディングを推奨
    // 勤怠修正申請 (前回と同じ)
    Route::post('/attendances/{attendance}/correction', [AttendanceController::class, 'requestCorrection'])->name('attendances.request_correction');
    // 申請一覧ページ (新規)
    Route::get('/correction-requests', [UserCorrectionRequestController::class, 'index'])->name('correction_requests.index');
});

// 管理者認証関連
Route::prefix('admin')->name('admin.')->group(function () {
    // ログイン画面表示
    Route::get('/login', [AdminLoginController::class, 'create'])->name('login.create')->middleware('guest'); // 'guest:admin' ではなく 'guest'
    // ログイン処理
    Route::post('/login', [AdminLoginController::class, 'store'])->name('login.store')->middleware('guest'); // 'guest:admin' ではなく 'guest'
    // ログアウト処理
    Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout')->middleware('auth', 'admin.check'); // 'auth:admin' ではなく 'auth' とカスタムミドルウェア

    // 管理者専用ページ (ログイン成功後のリダイレクト先やその他管理者機能)
    Route::middleware(['auth', 'admin.check'])->group(function () {
        // 勤怠一覧 (日付指定可能)
        Route::get('/attendances/list/{date?}', [AdminAttendanceController::class, 'index'])->name('attendances.list');
        // 勤怠詳細 (管理者用)
        Route::get('/attendances/{attendance}/show', [AdminAttendanceController::class, 'show'])->name('attendances.show');
        Route::put('/attendances/{attendance}/update', [AdminAttendanceController::class, 'update'])->name('attendances.update');

        // スタッフ一覧 (新規)
        Route::get('/staff/list', [StaffController::class, 'index'])->name('staff.list');
        // スタッフ別勤怠一覧 (新規 - User IDで指定)
        Route::get('/attendances/staff/{user}/{month?}', [AdminAttendanceController::class, 'listByStaff'])->name('attendances.list_by_staff');
        // CSV出力 (新規)
        Route::get('/attendances/staff/{user}/{month}/export', [AdminAttendanceController::class, 'exportCsvByStaff'])->name('attendances.export_csv_by_staff');

        // 申請一覧 (管理者用 - 新規)
        Route::get('/correction-requests', [AdminCorrectionRequestController::class, 'index'])->name('correction_requests.index');
        // 修正申請承認画面 (管理者用 - 新規)
        Route::get('/correction-requests/{correction_request}/approve', [AdminCorrectionRequestController::class, 'showApprovalForm'])->name('correction_requests.show_approval_form');
        // 申請承認/却下処理 (管理者用 - 新規)
        Route::post('/correction-requests/{correction_request}/process', [AdminCorrectionRequestController::class, 'processRequest'])->name('correction_requests.process');
    });
});