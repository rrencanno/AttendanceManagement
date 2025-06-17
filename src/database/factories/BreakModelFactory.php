<?php

namespace Database\Factories;

use App\Models\BreakModel;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BreakModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BreakModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $breakStartTime = Carbon::now()->hour(12)->minute(0)->second(0);
        $breakEndTime = $breakStartTime->copy()->addHour();

        return [
            'break_start_time' => $breakStartTime,
            'break_end_time' => $breakEndTime,
        ];
    }

    /**
     * 特定の勤怠記録に基づいた休憩時間を生成する
     *
     * @param Attendance $attendance
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function forAttendance(Attendance $attendance)
    {
        return $this->state(function (array $attributes) use ($attendance) {
            if (!$attendance->clock_in_time || !$attendance->clock_out_time) {
                return [];
            }

            // 休憩開始時刻 (例: 出勤から2時間後～退勤2時間前の間、お昼休憩を想定)
            $minBreakStart = Carbon::instance($attendance->clock_in_time)->addHours(2);
            $maxBreakStart = Carbon::instance($attendance->clock_out_time)->subHours(2);

            if ($minBreakStart->greaterThanOrEqualTo($maxBreakStart)) {
                return [];
            }

            // 休憩開始時刻の時間帯を11:00～13:00に制限
            $possibleBreakStartHourMin = max(11, $minBreakStart->hour);
            $possibleBreakStartHourMax = min(13, $maxBreakStart->hour);

            if ($possibleBreakStartHourMin > $possibleBreakStartHourMax) return [];

            $breakStartHour = $this->faker->numberBetween($possibleBreakStartHourMin, $possibleBreakStartHourMax);
            $breakStartMinute = $this->faker->randomElement([0, 15, 30]);
            $breakStartTime = Carbon::instance($attendance->work_date)->hour($breakStartHour)->minute($breakStartMinute);

            if ($breakStartTime->lessThan($minBreakStart) || $breakStartTime->greaterThan($maxBreakStart)) {
                return [];
            }

            // 休憩時間 (45分～75分)
            $breakDurationMinutes = $this->faker->randomElement([45, 60, 75]);
            $breakEndTime = $breakStartTime->copy()->addMinutes($breakDurationMinutes);

            // 休憩終了が退勤時刻を超えないように
            if ($breakEndTime->greaterThan(Carbon::instance($attendance->clock_out_time))) {
                $breakEndTime = Carbon::instance($attendance->clock_out_time);
            }
             if ($breakEndTime->lessThanOrEqualTo($breakStartTime)) return [];


            return [
                'break_start_time' => $breakStartTime,
                'break_end_time' => $breakEndTime,
            ];
        });
    }
}
