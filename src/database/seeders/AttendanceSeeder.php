<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $users = User::where('is_admin', false)->get();
        $faker = \Faker\Factory::create('ja_JP');
        $appTimezone = config('app.timezone', 'Asia/Tokyo');

        foreach ($users as $user) {
            for ($i = 1; $i <= $faker->numberBetween(15, 25); $i++) {
                // 1. 勤務日を決定 (JSTの0時0分0秒に設定)
                $workDate = Carbon::today($appTimezone)->subDays($i)->startOfDay();

                if (Attendance::where('user_id', $user->id)->where('work_date', $workDate->toDateString())->exists()) {
                    continue;
                }

                // 2. JSTで出勤・退勤時刻を生成
                $clockInHour = $faker->numberBetween(8, 10);
                $clockInMinute = $faker->randomElement([0, 15, 30, 45]);
                // workDateを基準に時刻を設定
                $clockInTime = $workDate->copy()->hour($clockInHour)->minute($clockInMinute);

                $clockOutTime = null;
                if ($faker->boolean(95)) {
                    $workDurationHours = $faker->numberBetween(7, 9);
                    $clockOutTime = $clockInTime->copy()->addHours($workDurationHours);
                }

                // 3. 勤怠記録を作成
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $workDate->toDateString(),
                    'clock_in_time' => $clockInTime,
                    'clock_out_time' => $clockOutTime,
                    'note' => $faker->optional(0.3)->sentence,
                ]);

                // 4. JSTで休憩時間を作成
                if ($attendance->clock_out_time) {
                    if ($faker->boolean(90)) {
                        // 12時台の休憩を生成
                        $breakStartTime = $workDate->copy()->hour(12)->minute(0);
                        $breakEndTime = $workDate->copy()->hour(13)->minute(0);

                        // 勤務時間内に収まっている場合のみ作成
                        if ($breakStartTime->gte($clockInTime) && $breakEndTime->lte($clockOutTime)) {
                            BreakModel::create([
                                'attendance_id' => $attendance->id,
                                'break_start_time' => $breakStartTime,
                                'break_end_time' => $breakEndTime,
                            ]);
                        }
                    }
                }
            }
        }
    }
}
