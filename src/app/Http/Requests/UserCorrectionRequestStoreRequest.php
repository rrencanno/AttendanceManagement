<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance; // 追加

class UserCorrectionRequestStoreRequest extends FormRequest
{
    public function authorize()
    {
        $attendance = $this->route('attendance');
        // $attendance が Attendance インスタンスであることを確認し、user_id をチェック
        return $attendance instanceof Attendance && $attendance->user_id == Auth::id();
    }

    public function rules()
    {
        return [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => ['required', 'date_format:H:i', 'after:clock_in_time'],
            'break_start_time'   => ['nullable', 'array'],
            'break_start_time.*' => ['nullable', 'date_format:H:i'],
            'break_end_time'     => ['nullable', 'array'],
            'break_end_time.*'   => ['nullable', 'date_format:H:i', function ($attribute, $value, $fail) {
                // break_start_time.* と対応するインデックスを取得
                $index = explode('.', $attribute)[1];
                $startTime = $this->input('break_start_time.' . $index);
                if ($startTime && $value && strtotime($value) <= strtotime($startTime)) {
                    $fail(($index + 1) . '番目の休憩終了時間は、開始時間より後である必要があります。');
                }
                if ($startTime && !$value) {
                    $fail(($index + 1) . '番目の休憩終了時間を入力してください。');
                }
                if (!$startTime && $value) {
                    $fail(($index + 1) . '番目の休憩開始時間を入力してください。');
                }
            }],
            'requested_note' => ['nullable', 'string', 'max:500'], // 備考
        ];
    }

    public function attributes()
    {
        return [
            'clock_in_time' => '出勤時間',
            'clock_out_time' => '退勤時間',
            'break_start_time' => '休憩開始時間',
            'break_start_time.*' => '休憩開始時間',
            'break_end_time' => '休憩終了時間',
            'break_end_time.*' => '休憩終了時間',
            'requested_note' => '備考(申請理由)',
        ];
    }
}
