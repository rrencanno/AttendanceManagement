<?php

namespace Database\Factories;

use App\Models\BreakModel;
use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakModelFactory extends Factory
{
    protected $model = BreakModel::class;

    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'break_start_time' => null,
            'break_end_time' => null,
        ];
    }
}
