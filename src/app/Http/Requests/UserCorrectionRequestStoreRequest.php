<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

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
        // $workDate は Carbon インスタンスとして取得
        $carbonWorkDate = Carbon::parse($this->route('attendance')->work_date);
        // 日付部分のみ (Y-m-d 形式の文字列) を取得
        $dateString = $carbonWorkDate->toDateString();

        return [
            'clock_in_time' => ['required', 'date_format:H:i'],
            'clock_out_time' => [
                'required',
                'date_format:H:i',
                // 'after' ルールは時刻のみの比較だと日付を跨ぐ場合に意図通りにならないことがあるので注意。
                // 今回は同じ日の中での比較なので、'H:i' 形式なら問題ないことが多いが、
                // より厳密にはカスタムルールで日付と時刻を結合して比較する。
                // ここではメッセージ変更が主なので、ルール自体は維持する。
                function ($attribute, $value, $fail) {
                    $clockInTime = $this->input('clock_in_time');
                    if ($clockInTime && strtotime($value) <= strtotime($clockInTime)) {
                        // メッセージは messages() メソッドで設定するので、ここではキーを意識する
                        $fail('出勤時間もしくは退勤時間が不適切な値です。');
                    }
                },
            ],
            'break_start_time'   => ['nullable', 'array'],
            'break_start_time.*' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($dateString) { // $workDate の代わりに $dateString を使用
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
                function ($attribute, $value, $fail) use ($dateString) { // $workDate の代わりに $dateString を使用
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
            'requested_note' => ['required', 'string', 'max:500'], // 備考
        ];
    }

    public function messages()
    {
        return [
            // 'clock_out_time.after_or_equal_clock_in' => '出勤時間もしくは退勤時間が不適切な値です。', // clock_out_time のカスタムルールキーに対するメッセージ
            // 'clock_out_time.after' => '出勤時間もしくは退勤時間が不適切な値です。', // もし after ルールを直接使う場合のメッセージ
            'requested_note.required' => '備考を記入してください。',
            // 休憩時間のカスタムルールで $fail() に直接メッセージを指定したため、ここでは不要
            // もしキーで指定した場合はここに追加
            // 'break_start_time.*.in_work_hours' => '休憩時間が勤務時間外です。',
            // 'break_end_time.*.in_work_hours' => '休憩時間が勤務時間外です。',
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
