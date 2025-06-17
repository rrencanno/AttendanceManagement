<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->is_admin;
    }

    public function rules()
    {
        $attendance = $this->route('attendance');
        $carbonWorkDate = Carbon::parse($attendance->work_date);
        $dateString = $carbonWorkDate->toDateString();

        return [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => [
                'required',
                'date_format:H:i',
                function ($attribute, $value, $fail) {
                    $clockInTime = $this->input('clock_in_time');
                    if ($clockInTime && strtotime($value) <= strtotime($clockInTime)) {
                        $fail('出勤時間もしくは退勤時間が不適切な値です。');
                    }
                },
            ],
            'break_start_time'   => ['nullable', 'array'],
            'break_start_time.*' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($dateString) {
                    $clockInFull = $this->input('clock_in_time') ? Carbon::parse($dateString . ' ' . $this->input('clock_in_time')) : null;
                    $clockOutFull = $this->input('clock_out_time') ? Carbon::parse($dateString . ' ' . $this->input('clock_out_time')) : null;
                    $breakStartFull = $value ? Carbon::parse($dateString . ' ' . $value) : null;

                    if ($breakStartFull && $clockInFull && $breakStartFull->lt($clockInFull)) {
                        $fail('休憩時間が勤務時間外です。');
                        return;
                    }
                    if ($breakStartFull && $clockOutFull && $breakStartFull->gt($clockOutFull)) {
                        $fail('休憩時間が勤務時間外です。');
                        return;
                    }
                },
            ],
            'break_end_time'     => ['nullable', 'array'],
            'break_end_time.*'   => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($dateString) {
                    $index = explode('.', $attribute)[1];
                    $startTime = $this->input('break_start_time.' . $index);
                    $breakEndFull = $value ? Carbon::parse($dateString . ' ' . $value) : null;
                    $breakStartFull = $startTime ? Carbon::parse($dateString . ' ' . $startTime) : null;
                    $clockOutFull = $this->input('clock_out_time') ? Carbon::parse($dateString . ' ' . $this->input('clock_out_time')) : null;

                    if ($breakStartFull && $breakEndFull && $breakEndFull->lte($breakStartFull)) {
                        $fail(($index + 1) . '番目の休憩終了時間は、開始時間より後である必要があります。');
                        return;
                    }
                    if ($breakEndFull && $clockOutFull && $breakEndFull->gt($clockOutFull)) {
                        $fail('休憩時間が勤務時間外です。');
                        return;
                    }
                },
            ],
            'remarks' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages()
    {
        return [
            'remarks.required' => '備考を記入してください。',
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
