<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\AttendanceCorrectionRequest;
use App\Http\Requests\UserCorrectionRequestStoreRequest;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の最新の勤怠記録を取得
        $latestAttendanceToday = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->latest('id')
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
                    $status = 'on_break';
                } else {
                    $status = 'working';
                }
            } else {
                $status = 'finished_today';
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

        // 前日の勤務が未退勤のままか確認
        $yesterdayAttendanceNotClockedOut = Attendance::where('user_id', $user->id)
            ->where('work_date', Carbon::yesterday()->toDateString())
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();
            if ($yesterdayAttendanceNotClockedOut) {
                // 前日の勤務を強制的に退勤させる (前日の23:59:59に退勤したことにする)
                $endOfYesterday = Carbon::parse($yesterdayAttendanceNotClockedOut->work_date)->endOfDay();
                $yesterdayAttendanceNotClockedOut->update(['clock_out_time' => $endOfYesterday]);
        
                // ユーザーにメッセージを表示して、再度出勤ボタンを押してもらう
                return redirect()->route('attendances.index')
                                 ->with('warning', '前日の退勤打刻がありませんでした。システムにより前日23:59に退勤処理を行いました。本日の出勤を再度行ってください。');
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

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendances.index')->with('error', '出勤記録がありません。');
        }

        // 休憩中であれば、先に休憩を終了させる (システム上、あり得ないが念のため)
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

        $attendance = Attendance::where('user_id', $user->id)
            ->where('work_date', $today)
            ->whereNull('clock_out_time')
            ->latest('id')
            ->first();

        if (!$attendance) {
            return redirect()->route('attendances.index')->with('error', '出勤記録がありません。');
        }

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

    public function list(Request $request)
    {
        $user = Auth::user();

        $targetMonthInput = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $targetMonth = Carbon::parse($targetMonthInput)->startOfMonth();
        } catch (\Exception $e) {
            $targetMonth = Carbon::now()->startOfMonth();
        }


        $firstDayOfMonth = $targetMonth->copy();
        $lastDayOfMonth = $targetMonth->copy()->endOfMonth();

        $period = CarbonPeriod::create($firstDayOfMonth, $lastDayOfMonth);
        $datesInMonth = [];
        foreach ($period as $date) {
            $datesInMonth[] = $date->copy();
        }

        // ログインユーザーの指定された月の勤怠記録を取得し、日付をキーにした連想配列に整理
        $attendancesRecords = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$firstDayOfMonth->toDateString(), $lastDayOfMonth->toDateString()])
            ->with('breaks')
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->toDateString();
            });

        // ビューに渡すための日毎のデータ配列を作成
        $dailyData = [];
        foreach ($datesInMonth as $date) {
            $dateString = $date->toDateString();
            $dailyData[] = [
                'date' => $date,
                'attendance' => $attendancesRecords->get($dateString)
            ];
        }

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('attendances.list', compact(
            'dailyData',
            'targetMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    // 勤怠詳細ページのメソッド
    public function show(Request $request, Attendance $attendance)
    {
        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $attendance->load(['user', 'breaks', 'latestPendingCorrectionRequest.user']);

        $displayBreaks = [];
        if ($attendance->latestPendingCorrectionRequest && $attendance->latestPendingCorrectionRequest->status == 'pending') {
            if (is_array($attendance->latestPendingCorrectionRequest->requested_break_details)) {
                foreach ($attendance->latestPendingCorrectionRequest->requested_break_details as $breakDetail) {
                    $displayBreaks[] = (object)[
                        'start' => $breakDetail['start'] ?? null,
                        'end' => $breakDetail['end'] ?? null,
                    ];
                }
            }
        } else {
            foreach ($attendance->breaks as $break) {
                $displayBreaks[] = (object)[
                    'start' => $break->break_start_time,
                    'end' => $break->break_end_time,
                ];
            }
        }

        if (empty($displayBreaks)) {
            $displayBreaks[] = (object)['start' => null, 'end' => null];
        }

        $from = $request->input('from', 'list');
        $returnMonth = $request->input('return_month', Carbon::parse($attendance->work_date)->format('Y-m'));

        $backUrl = route('attendances.list', ['month' => $returnMonth]);
        if ($from === 'requests') {
            $backUrl = route('correction_requests.index');
        }
        
        return view('attendances.show', compact('attendance', 'displayBreaks', 'backUrl'));
    }

    public function requestCorrection(UserCorrectionRequestStoreRequest $request, Attendance $attendance)
    {
        if ($attendance->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($attendance->latestPendingCorrectionRequest && $attendance->latestPendingCorrectionRequest->status == 'pending') {
            return redirect()->route('attendances.show', $attendance)
                ->with('error', '既に修正申請中です。管理者の承認をお待ちください。');
        }

        $workDate = Carbon::parse($attendance->work_date);

        // 休憩詳細の整形
        $requestedBreakDetails = [];
        $breakStartTimes = $request->input('break_start_time', []);
        $breakEndTimes = $request->input('break_end_time', []);

        foreach ($breakStartTimes as $index => $startTime) {
            $endTime = $breakEndTimes[$index] ?? null;
            if (!empty($startTime) && !empty($endTime)) {
                $requestedBreakDetails[] = [
                    'start' => $startTime,
                    'end' => $endTime,
                ];
            } elseif (!empty($startTime) && empty($endTime)) {
                 $requestedBreakDetails[] = ['start' => $startTime, 'end' => null];
            } elseif (empty($startTime) && !empty($endTime)) {
                 $requestedBreakDetails[] = ['start' => null, 'end' => $endTime];
            }
        }

        AttendanceCorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'requested_clock_in_time' => $request->input('clock_in_time') ? $workDate->copy()->setTimeFromTimeString($request->input('clock_in_time')) : null,
            'requested_clock_out_time' => $request->input('clock_out_time') ? $workDate->copy()->setTimeFromTimeString($request->input('clock_out_time')) : null,
            'requested_break_details' => !empty($requestedBreakDetails) ? $requestedBreakDetails : null,
            'requested_note' => $request->input('requested_note'),
            'status' => 'pending',
        ]);

        return redirect()->route('attendances.show', $attendance)
            ->with('status', '勤怠修正を申請しました。');
    }
}
