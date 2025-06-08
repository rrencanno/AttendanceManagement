<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class CorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->query('status', 'pending'); // デフォルトは 'pending'

        $query = AttendanceCorrectionRequest::with(['user', 'attendance.user'])
                                       ->orderBy('created_at', 'desc');

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'approved') {
            $query->where('status', 'approved');
        }
        // 却下機能がないので、rejected は考慮しない

        $correctionRequests = $query->paginate(10);

        return view('admin.correction_requests.index', compact('correctionRequests', 'statusFilter'));
    }

    public function showApprovalForm(AttendanceCorrectionRequest $correction_request)
    {
        // 申請情報と、関連する現在の勤怠情報、申請者の情報をロード
        // 申請された休憩時間も整形してビューに渡す
        $correction_request->load(['user', 'attendance.user', 'attendance.breaks']);

        // 申請された休憩情報を整形 (ビューで表示しやすくするため)
        $requestedBreaks = [];
        if (is_array($correction_request->requested_break_details)) {
            foreach ($correction_request->requested_break_details as $breakDetail) {
                $requestedBreaks[] = (object)[
                    'start' => $breakDetail['start'] ?? null,
                    'end' => $breakDetail['end'] ?? null,
                ];
            }
        }
        // もし申請された休憩がなければ、空の入力欄を1つ表示するためにダミーデータを渡すことも検討
        if (empty($requestedBreaks)) {
            $requestedBreaks[] = (object)['start' => null, 'end' => null];
        }

        return view('admin.correction_requests.approve_form', compact('correction_request', 'requestedBreaks'));
    }

    public function processRequest(Request $request, AttendanceCorrectionRequest $correction_request)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve'], // 今回は承認のみ
        ]);

        if ($correction_request->status !== 'pending') {
            return redirect()->route('admin.correction_requests.index')->with('error', 'この申請は既に処理済みです。');
        }

        if ($validated['action'] === 'approve') {
            $attendance = $correction_request->attendance;
            if (!$attendance) {
                return redirect()->back()->with('error', '関連する勤怠記録が見つかりません。処理を中止しました。')->withInput();
            }

            $workDate = Carbon::parse($attendance->work_date);

            // 1. 勤怠データの更新
            // 出勤時刻
            if ($correction_request->requested_clock_in_time) {
                // AttendanceCorrectionRequest モデルで datetime にキャストされているのでそのまま使える
                $attendance->clock_in_time = $correction_request->requested_clock_in_time;
            }
            // 退勤時刻
            if ($correction_request->requested_clock_out_time) {
                $attendance->clock_out_time = $correction_request->requested_clock_out_time;
            }
            // 備考 (申請の requested_note を勤怠の note に反映)
            $attendance->note = $correction_request->requested_note; // Attendanceモデルの$fillableに'note'が必要
            $attendance->save();

            // 2. 休憩時間の更新
            if (is_array($correction_request->requested_break_details)) {
                $attendance->breaks()->delete(); // 既存の休憩を削除
                foreach ($correction_request->requested_break_details as $breakDetail) {
                    // 申請された休憩時間の start と end が両方存在する場合のみ登録
                    if (!empty($breakDetail['start']) && !empty($breakDetail['end'])) {
                        BreakModel::create([
                            'attendance_id' => $attendance->id,
                            // requested_break_details には HH:MM 形式で入っているので、日付を付与
                            'break_start_time' => $workDate->copy()->setTimeFromTimeString($breakDetail['start']),
                            'break_end_time' => $workDate->copy()->setTimeFromTimeString($breakDetail['end']),
                        ]);
                    }
                }
            }

            // 3. 申請レコードのステータス更新
            $correction_request->status = 'approved';
            $correction_request->save();

            return redirect()->route('admin.correction_requests.index', ['status' => 'approved'])
                             ->with('status', '申請ID: ' . $correction_request->id . ' を承認し、勤怠情報を更新しました。');
        }

        return redirect()->back()->with('error', '不正な操作です。');
    }
}
