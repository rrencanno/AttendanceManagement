<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // 過去30日以内のランダムな日付
        $workDate = $this->faker->dateTimeBetween('-30 days', 'now');
        $workDateCarbon = Carbon::instance($workDate);

        // 出勤時刻 (例: 8:00 - 10:00 の間)
        $clockInHour = $this->faker->numberBetween(8, 10);
        $clockInMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $clockInTime = $workDateCarbon->copy()->hour($clockInHour)->minute($clockInMinute)->second(0);

        // 退勤時刻 (例: 出勤から7-9時間後)
        // ただし、出勤打刻がある場合のみ
        $clockOutTime = null;
        if ($this->faker->boolean(95)) { // 95%の確率で退勤打刻あり
            $workDurationHours = $this->faker->numberBetween(7, 9);
            $clockOutHour = $clockInTime->hour + $workDurationHours;
            $clockOutMinute = $this->faker->randomElement([0, 15, 30, 45]);

            // 24時を超える場合は調整 (今回は簡単のため考慮外とするか、調整する)
            if ($clockOutHour >= 24) {
                $clockOutHour = 23;
                $clockOutMinute = 45;
            }
            $clockOutTime = $workDateCarbon->copy()->hour($clockOutHour)->minute($clockOutMinute)->second(0);

            // clock_out_time が clock_in_time より前にならないように最終調整
            if ($clockOutTime->lessThanOrEqualTo($clockInTime)) {
                $clockOutTime = $clockInTime->copy()->addHours(8); // 最低8時間後
            }
        }


        return [
            // 'user_id' はSeederで指定する想定
            'work_date' => $workDateCarbon->format('Y-m-d'),
            'clock_in_time' => $clockInTime,
            'clock_out_time' => $clockOutTime,
            'note' => $this->faker->optional(0.3)->sentence, // 30%の確率で備考あり
        ];
    }
}
