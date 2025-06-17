<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakModel extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start_time',
        'break_end_time',
    ];

    protected $casts = [
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
