<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
}
