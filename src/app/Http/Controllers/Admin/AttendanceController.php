<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\User;
use Carbon\Carbon;
use App\Http\Requests\Admin\AttendanceUpdateRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request, $date = null)
    {
        // 日付の決定: パラメータがあればそれを使い、なければ今日
        try {
            $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        } catch (\Exception $e) {
            $targetDate = Carbon::today(); // 不正な日付の場合は今日にする
        }

        // その日の勤怠記録を全ユーザー分取得 (User情報も一緒に)
        // 休憩時間や合計勤務時間はAttendanceモデルのアクセサで計算される想定
        $attendances = Attendance::where('work_date', $targetDate->toDateString())
                                ->with(['user', 'breaks']) // ユーザー情報と休憩情報をEager load
                                ->orderBy('user_id') // または社員番号順など
                                ->paginate(10); // 1ページあたり10件表示

        // 前日と翌日のナビゲーション用
        $prevDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        return view('admin.attendances.list', compact(
            'attendances',
            'targetDate',
            'prevDate',
            'nextDate'
        ));
    }

    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'breaks']); // ユーザー情報と休憩情報をEager load

        // 休憩時間は複数ある可能性を考慮し、ビューでループ表示できるようにする
        // もし休憩がなければ、空の入力欄を1つ表示するためにダミーデータを渡すことも検討
        $breaks = $attendance->breaks->isNotEmpty() ? $attendance->breaks : collect([(object)['break_start_time' => null, 'break_end_time' => null]]);


        return view('admin.attendances.show', compact('attendance', 'breaks'));
    }

    public function update(AttendanceUpdateRequest $request, Attendance $attendance)
    {
        // --- 勤怠時間の更新 ---
        $workDate = Carbon::parse($attendance->work_date);
        $attendance->clock_in_time = $request->input('clock_in_time') ? $workDate->copy()->setTimeFromTimeString($request->input('clock_in_time')) : null;
        $attendance->clock_out_time = $request->input('clock_out_time') ? $workDate->copy()->setTimeFromTimeString($request->input('clock_out_time')) : null;
        $attendance->note = $request->input('remarks');
        $attendance->save();

        // --- 休憩時間の更新 ---
        // 既存の休憩記録を一旦すべて削除
        $attendance->breaks()->delete();

        // 新しい休憩記録を登録
        $breakStartTimes = $request->input('break_start_time', []);
        $breakEndTimes = $request->input('break_end_time', []);

        foreach ($breakStartTimes as $index => $startTime) {
            $endTime = $breakEndTimes[$index] ?? null;
            // 開始時間と終了時間の両方が入力されている場合のみ登録
            if (!empty($startTime) && !empty($endTime)) {
                BreakModel::create([
                    'attendance_id' => $attendance->id,
                    'break_start_time' => $workDate->copy()->setTimeFromTimeString($startTime), // 日付情報を付与してdatetimeとして保存
                    'break_end_time' => $workDate->copy()->setTimeFromTimeString($endTime),   // 同上
                ]);
            }
        }

        return redirect()->route('admin.attendances.show', $attendance->id)
                         ->with('status', '勤怠情報を更新しました。');
    }

    public function listByStaff(Request $request, User $user, $month = null)
    {
        try {
            $targetMonth = $month ? Carbon::parse($month . "-01") : Carbon::now();
        } catch (\Exception $e) {
            $targetMonth = Carbon::now();
        }

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $targetMonth->year)
            ->whereMonth('work_date', $targetMonth->month)
            ->with('breaks')
            ->orderBy('work_date', 'asc')
            ->paginate(31); // 月の最大日数分表示できるように調整 (またはページネーションなしで全件取得も可)

        $prevMonth = $targetMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $targetMonth->copy()->addMonth()->format('Y-m');

        return view('admin.attendances.list_by_staff', compact( // ビュー名を指定
            'user',
            'attendances',
            'targetMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function exportCsvByStaff(User $user, $month)
    {
        try {
            $targetMonth = Carbon::parse($month . "-01");
        } catch (\Exception $e) {
            // 不正な月の場合はエラーまたはリダイレクト
            return redirect()->back()->with('error', '無効な月が指定されました。');
        }

        $fileName = $user->name . '_' . $targetMonth->format('Y年m月') . '_勤怠.csv';

        // ヘッダー（BOM付きUTF-8で日本語の文字化けを防ぐ）
        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        // CSVに出力するデータを取得 (ページネーションなしで全件)
        $attendancesToExport = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $targetMonth->year)
            ->whereMonth('work_date', $targetMonth->month)
            ->with('breaks')
            ->orderBy('work_date', 'asc')
            ->get();

        $callback = function() use ($attendancesToExport) {
            $file = fopen('php://output', 'w');
            // BOMを書き込む
            fwrite($file, "\xEF\xBB\xBF");

            // CSVヘッダー行
            fputcsv($file, ['日付', '曜日', '出勤時刻', '退勤時刻', '合計休憩時間(HH:MM)', '実労働時間(HH:MM)', '備考']);

            // CSVデータ行
            foreach ($attendancesToExport as $attendance) {
                $workDateCarbon = Carbon::parse($attendance->work_date);
                fputcsv($file, [
                    $workDateCarbon->format('Y/m/d'),
                    $workDateCarbon->isoFormat('ddd'),
                    $attendance->clock_in_time ? Carbon::parse($attendance->clock_in_time)->format('H:i') : '-',
                    $attendance->clock_out_time ? Carbon::parse($attendance->clock_out_time)->format('H:i') : '-',
                    $attendance->formatted_total_break_time ?? '00:00', // モデルアクセサ
                    $attendance->formatted_total_work_time ?? '00:00',  // モデルアクセサ
                    $attendance->remarks ?? '',
                ]);
            }
            fclose($file);
        };

        return new StreamedResponse($callback, 200, $headers);
    }
}
