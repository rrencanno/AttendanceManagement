<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('is_admin', false)->get();
        $faker = \Faker\Factory::create('ja_JP');

        foreach ($users as $user) {
            // 過去15~25日分の勤怠データを作成
            for ($i = 1; $i <= $faker->numberBetween(15, 25); $i++) {
                $workDate = Carbon::today()->subDays($i);

                // 同じ日に複数の勤怠レコードができないようにチェック
                if (Attendance::where('user_id', $user->id)->where('work_date', $workDate->format('Y-m-d'))->exists()) {
                    continue;
                }

                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $workDate->format('Y-m-d'),
                ]);

                if ($attendance->clock_out_time) {
                    // 70%の確率で休憩を1回作成
                    if ($faker->boolean(70)) {
                        $breakData = BreakModel::factory()->forAttendance($attendance)->make()->toArray();
                        if(!empty($breakData)){
                             BreakModel::create(array_merge(['attendance_id' => $attendance->id], $breakData));
                        }
                    }
                }
            }
        }
    }
}
