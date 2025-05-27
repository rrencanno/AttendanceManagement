<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        // 管理者であることの確認 (ルートミドルウェアで既に行っているが、念のため)
        return $this->user() && $this->user()->is_admin;
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
                $index = explode('.', $attribute)[1];
                $startTime = $this->input('break_start_time.' . $index);
                if ($startTime && $value && strtotime($value) <= strtotime($startTime)) {
                    $fail(($index + 1) . '番目の休憩終了時間は、開始時間より後である必要があります。');
                }
                if ($startTime && !$value) {
                    $fail(($index + 1) . '番目の休憩終了時間を入力してください（開始時間がある場合）。');
                }
                if (!$startTime && $value) {
                    $fail(($index + 1) . '番目の休憩開始時間を入力してください（終了時間がある場合）。');
                }
            }],
            'remarks' => ['nullable', 'string', 'max:500'],
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
            'remarks' => '備考',
        ];
    }
}
