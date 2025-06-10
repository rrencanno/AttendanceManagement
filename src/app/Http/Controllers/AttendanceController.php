<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\AttendanceCorrectionRequest;
use App\Http\Requests\UserCorrectionRequestStoreRequest; // FormRequest名を修正・統一
use Carbon\Carbon;
use Carbon\CarbonInterval; // 時間計算で使用
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

    public function list(Request $request)
    {
        $user = Auth::user();
        // リクエストから 'month' パラメータを取得。なければ現在の年月を使用 (YYYY-MM形式)
        $targetMonthInput = $request->input('month', Carbon::now()->format('Y-m'));
        try {
            $targetMonth = Carbon::parse($targetMonthInput)->startOfMonth();
        } catch (\Exception $e) {
            $targetMonth = Carbon::now()->startOfMonth();
        }


        $firstDayOfMonth = $targetMonth->copy();
        $lastDayOfMonth = $targetMonth->copy()->endOfMonth();

        // 指定された月の全ての日付を生成
        $period = CarbonPeriod::create($firstDayOfMonth, $lastDayOfMonth);
        $datesInMonth = [];
        foreach ($period as $date) {
            $datesInMonth[] = $date->copy(); // Carbonインスタンスとして保持
        }

        // ログインユーザーの指定された月の勤怠記録を取得し、日付をキーにした連想配列に変換
        $attendancesRecords = Attendance::where('user_id', $user->id)
            ->whereBetween('work_date', [$firstDayOfMonth->toDateString(), $lastDayOfMonth->toDateString()])
            ->with('breaks')
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->toDateString(); // YYYY-MM-DD 形式の文字列をキーに
            });

        // ビューに渡すための日毎のデータ配列を作成
        $dailyData = [];
        foreach ($datesInMonth as $date) {
            $dateString = $date->toDateString();
            $dailyData[] = [
                'date' => $date, // Carbonインスタンス
                'attendance' => $attendancesRecords->get($dateString) // 該当日の勤怠データ (なければnull)
            ];
        }

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('attendances.list', compact(
            'dailyData', // $attendances の代わりに $dailyData を渡す
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
        // 常に少なくとも1セットは表示・入力できるようにする
        if (empty($displayBreaks)) {
            $displayBreaks[] = (object)['start' => null, 'end' => null];
        }

        $returnMonth = $request->input('return_month', Carbon::parse($attendance->work_date)->format('Y-m'));

        return view('attendances.show', compact('attendance', 'displayBreaks', 'returnMonth'));
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
            } elseif (!empty($startTime) && empty($endTime)) { // 開始のみ入力された場合
                 $requestedBreakDetails[] = ['start' => $startTime, 'end' => null];
            } elseif (empty($startTime) && !empty($endTime)) { // 終了のみ入力された場合 (通常はエラーだが、ここではデータとして保持)
                 $requestedBreakDetails[] = ['start' => null, 'end' => $endTime];
            }
            // 両方空の場合は追加しない
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

        // 勤怠記録自体の備考も更新する場合はここで行う
        // $attendance->update(['remarks' => $request->input('some_other_remarks_field_if_any')]);

        return redirect()->route('attendances.show', $attendance)
            ->with('status', '勤怠修正を申請しました。');
    }
}
