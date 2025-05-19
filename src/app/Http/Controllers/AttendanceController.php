<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の最新の勤怠記録を取得
        $latestAttendanceToday = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->latest('id') // 念のため最新のものを取得
            ->first();

        $status = 'unstarted'; // 未出勤
        $activeBreak = null;

        if ($latestAttendanceToday) {
            if (empty($latestAttendanceToday->clock_out_time)) { // 退勤打刻がない場合
                // 休憩中か確認
                $activeBreak = BreakModel::where('attendance_id', $latestAttendanceToday->id)
                                  ->whereNull('break_end_time')
                                  ->latest('id')
                                  ->first();
                if ($activeBreak) {
                    $status = 'on_break'; // 休憩中
                } else {
                    $status = 'working'; // 勤務中 (出勤済み)
                }
            } else {
                $status = 'finished_today'; // 今日の勤務終了
            }
        }

        return view('attendances.index', compact('status', 'today', 'latestAttendanceToday', 'activeBreak'));
    }

    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 今日すでに出勤していて、まだ退勤していない記録があるか確認
        $existingAttendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->first();

        if ($existingAttendance) {
            return redirect()->route('attendances.index')->with('error', '既に出勤済みです。');
        }

        // 前日の勤務が未退勤のままか確認 (オプション)
        $yesterdayAttendanceNotClockedOut = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::yesterday()->toDateString())
            ->whereNull('clock_out_time')
            ->first();
        if ($yesterdayAttendanceNotClockedOut) {
            // 必要であれば前日の勤務を強制的に退勤させるか、エラーにする
            // $yesterdayAttendanceNotClockedOut->update(['clock_out_time' => Carbon::parse($yesterdayAttendanceNotClockedOut->work_date . ' 23:59:59')]);
            // return redirect()->route('attendances.index')->with('warning', '前日の退勤打刻がありませんでした。システムで記録しました。再度出勤してください。');
        }


        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'clock_in_time' => $now,
        ]);

        return redirect()->route('attendances.index')->with('status', '出勤しました。');
    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 今日の最新の未退勤の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendances.index')->with('error', '出勤記録がありません。');
        }

        // 休憩中であれば、先に休憩を終了させる (休憩終了時刻を退勤時刻と同じにするか、エラーにするか)
        $activeBreak = BreakModel::where('attendance_id', $attendance->id)
                          ->whereNull('break_end_time')
                          ->latest('id')
                          ->first();

        if ($activeBreak) {
            return redirect()->route('attendances.index')->with('error', '休憩中です。先に休憩を終了してください。');
        }

        $attendance->update([
            'clock_out_time' => $now,
        ]);

        return redirect()->route('attendances.index')->with('status', '退勤しました。');
    }

    public function startBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 今日の最新の未退勤の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendances.index')->with('error', '出勤していません。');
        }

        // 既に休憩中か確認
        $activeBreak = BreakModel::where('attendance_id', $attendance->id)
                          ->whereNull('break_end_time')
                          ->latest('id')
                          ->first();

        if ($activeBreak) {
            return redirect()->route('attendances.index')->with('error', '既に休憩中です。');
        }

        BreakModel::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $now,
        ]);

        return redirect()->route('attendances.index')->with('status', '休憩を開始しました。');
    }

    public function endBreak()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();
        $now = Carbon::now();

        // 今日の最新の未退勤の勤怠記録を取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (!$attendance) {
            // 通常、この状態にはなり得ないが念のため
            return redirect()->route('attendances.index')->with('error', '出勤記録がありません。');
        }

        // 最新の未終了の休憩記録を取得
        $activeBreak = BreakModel::where('attendance_id', $attendance->id)
                          ->whereNull('break_end_time')
                          ->latest('id')
                          ->first();

        if (!$activeBreak) {
            return redirect()->route('attendances.index')->with('error', '休憩中ではありません。');
        }

        $activeBreak->update([
            'break_end_time' => $now,
        ]);

        return redirect()->route('attendances.index')->with('status', '休憩を終了しました。');
    }
}
