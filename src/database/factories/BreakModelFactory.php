<?php

namespace Database\Factories;

use App\Models\BreakModel;
use App\Models\Attendance; // Attendanceモデルを使う場合
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
        // このFactoryはAttendanceに紐づけて使うことが多いため、
        // SeederやAttendanceFactoryのafterCreatingフックで時刻を調整することを推奨
        // ここでは仮の値を設定
        $breakStartTime = Carbon::now()->hour(12)->minute(0)->second(0);
        $breakEndTime = $breakStartTime->copy()->addHour();

        return [
            // 'attendance_id' はSeederで指定する想定
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
                // 出退勤がない場合は休憩も作らない (あるいはエラーを出す)
                // このstateは呼び出されないようにSeeder側で制御する
                return [];
            }

            // 休憩開始時刻 (例: 出勤から2時間後～退勤2時間前の間、お昼休憩を想定)
            $minBreakStart = Carbon::instance($attendance->clock_in_time)->addHours(2);
            $maxBreakStart = Carbon::instance($attendance->clock_out_time)->subHours(2); // 休憩1時間と作業1時間を考慮

            if ($minBreakStart->greaterThanOrEqualTo($maxBreakStart)) {
                // 休憩時間が取れない場合 (例: 勤務時間が短い)
                return []; // 何も作らない (Seeder側で制御)
            }

            // より具体的に、例えば11:30～13:00の間で開始するなど
            $possibleBreakStartHourMin = max(11, $minBreakStart->hour);
            $possibleBreakStartHourMax = min(13, $maxBreakStart->hour);

            if ($possibleBreakStartHourMin > $possibleBreakStartHourMax) return [];

            $breakStartHour = $this->faker->numberBetween($possibleBreakStartHourMin, $possibleBreakStartHourMax);
            $breakStartMinute = $this->faker->randomElement([0, 15, 30]);
            $breakStartTime = Carbon::instance($attendance->work_date)->hour($breakStartHour)->minute($breakStartMinute);

            if ($breakStartTime->lessThan($minBreakStart) || $breakStartTime->greaterThan($maxBreakStart)) {
                return []; // 範囲外なら作らない
            }

            // 休憩時間 (例: 45分～75分)
            $breakDurationMinutes = $this->faker->randomElement([45, 60, 75]);
            $breakEndTime = $breakStartTime->copy()->addMinutes($breakDurationMinutes);

            // 休憩終了が退勤時刻を超えないように
            if ($breakEndTime->greaterThan(Carbon::instance($attendance->clock_out_time))) {
                $breakEndTime = Carbon::instance($attendance->clock_out_time); // 退勤時刻に合わせるか、少し前にする
            }
             if ($breakEndTime->lessThanOrEqualTo($breakStartTime)) return [];


            return [
                'break_start_time' => $breakStartTime,
                'break_end_time' => $breakEndTime,
            ];
        });
    }
}
