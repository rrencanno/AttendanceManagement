<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_time',
        'clock_out_time',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date', // date型として扱う
        'clock_in_time' => 'datetime', // datetime型として扱う
        'clock_out_time' => 'datetime', // datetime型として扱う
    ];

    // リレーションシップ
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakModel::class); // モデル名を BreakModel にした場合
    }

    // 合計休憩時間をフォーマットして取得するアクセサ (例: "01:00")
    public function getFormattedTotalBreakTimeAttribute()
    {
        if ($this->breaks->isEmpty()) {
            return '00:00';
        }

        $totalBreakSeconds = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_start_time && $break->break_end_time) {
                $start = Carbon::parse($break->break_start_time);
                $end = Carbon::parse($break->break_end_time);
                $totalBreakSeconds += $start->diffInSeconds($end);
            }
        }

        if ($totalBreakSeconds == 0) return '00:00';
        return CarbonInterval::seconds($totalBreakSeconds)->cascade()->format('%H:%I');
    }

    // 合計実労働時間をフォーマットして取得するアクセサ (例: "08:00")
    public function getFormattedTotalWorkTimeAttribute()
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return '00:00';
        }

        $clockIn = Carbon::parse($this->clock_in_time);
        $clockOut = Carbon::parse($this->clock_out_time);

        // 勤務時間 (秒)
        $totalWorkSeconds = $clockIn->diffInSeconds($clockOut);

        // 合計休憩時間 (秒)
        $totalBreakSeconds = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_start_time && $break->break_end_time) {
                $start = Carbon::parse($break->break_start_time);
                $end = Carbon::parse($break->break_end_time);
                $totalBreakSeconds += $start->diffInSeconds($end);
            }
        }

        $actualWorkSeconds = $totalWorkSeconds - $totalBreakSeconds;

        if ($actualWorkSeconds <= 0) return '00:00';

        return CarbonInterval::seconds($actualWorkSeconds)->cascade()->format('%H:%I');
    }

    // 最新の保留中の修正申請を取得
    public function latestPendingCorrectionRequest() // <- このメソッドです
    {
        return $this->hasOne(AttendanceCorrectionRequest::class)->where('status', 'pending')->latestOfMany();
    }

    // 全ての修正申請 (履歴として必要な場合)
    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class)->orderBy('created_at', 'desc');
    }
}
