<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in_time',
        'requested_clock_out_time',
        'requested_break_details',
        'requested_note',
        'status',
    ];

    protected $casts = [
        'requested_clock_in_time' => 'datetime',
        'requested_clock_out_time' => 'datetime',
        'requested_break_details' => 'array',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
