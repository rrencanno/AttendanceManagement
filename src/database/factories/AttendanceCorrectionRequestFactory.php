<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceCorrectionRequestFactory extends Factory
{
    protected $model = AttendanceCorrectionRequest::class;

    public function definition()
    {
        $workDate = Carbon::today();
        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(), // 申請者
            'requested_clock_in_time' => $workDate->copy()->hour(9)->minute(30),
            'requested_clock_out_time' => $workDate->copy()->hour(18)->minute(30),
            'requested_break_details' => json_encode([['start' => '12:00', 'end' => '13:00']]),
            'requested_note' => $this->faker->sentence,
            'status' => 'pending',
        ];
    }
}
