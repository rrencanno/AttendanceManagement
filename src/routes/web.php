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

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ユーザー認証関連
Route::middleware(['auth', 'verified'])->group(function () {
    Route::controller(AttendanceController::class)->prefix('attendances')->name('attendances.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/clock-in', 'clockIn')->name('clockin');
        Route::post('/clock-out', 'clockOut')->name('clockout');
        Route::post('/break/start', 'startBreak')->name('break.start');
        Route::post('/break/end', 'endBreak')->name('break.end');
        Route::get('/list', 'list')->name('list');
        Route::get('/{attendance}', 'show')->name('show');
        Route::post('/{attendance}/correction', 'requestCorrection')->name('request_correction');
    });

    Route::controller(UserCorrectionRequestController::class)->prefix('correction-requests')->name('correction_requests.')->group(function () {
        Route::get('/', 'index')->name('index');
    });
});

// 管理者認証関連
Route::prefix('admin')->name('admin.')->group(function () {
    Route::controller(AdminLoginController::class)->group(function () {
        Route::get('/login', 'create')->name('login.create')->middleware('guest');
        Route::post('/login', 'store')->name('login.store')->middleware('guest');
        Route::post('/logout', 'destroy')->name('logout')->middleware('auth', 'admin.check');
    });

    Route::middleware(['auth', 'admin.check'])->group(function () {
        Route::controller(AdminAttendanceController::class)->prefix('attendances')->name('attendances.')->group(function () {
            Route::get('/list/{date?}', 'index')->name('list');
            Route::get('/{attendance}/show', 'show')->name('show');
            Route::put('/{attendance}/update', 'update')->name('update');
            Route::get('/staff/{user}/{month?}', 'listByStaff')->name('list_by_staff');
            Route::get('/staff/{user}/{month}/export', 'exportCsvByStaff')->name('export_csv_by_staff');
        });

        Route::controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
            Route::get('/list', 'index')->name('list');
        });

        Route::controller(AdminCorrectionRequestController::class)->prefix('correction-requests')->name('correction_requests.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{correction_request}/approve', 'showApprovalForm')->name('show_approval_form');
            Route::post('/{correction_request}/process', 'processRequest')->name('process');
        });
    });
});
