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
        $users = User::all();
        $faker = \Faker\Factory::create('ja_JP');

        foreach ($users as $user) {
            // 各ユーザーに対して、過去N日分の勤怠データを作成 (過去15～25日分)
            for ($i = 1; $i <= $faker->numberBetween(15, 25); $i++) {
                $workDate = Carbon::today()->subDays($i);

                // 同じ日に複数の勤怠レコードができないようにチェック
                if (Attendance::where('user_id', $user->id)->where('work_date', $workDate->format('Y-m-d'))->exists()) {
                    continue;
                }

                // AttendanceFactoryを使って勤怠記録を作成
                // この際、user_id と work_date は明示的に指定
                $attendance = Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $workDate->format('Y-m-d'), // Factory内で生成されるwork_dateを上書き
                ]);

                // 作成した勤怠記録に退勤記録があり、休憩が取れる時間がある場合のみ休憩を作成
                if ($attendance->clock_out_time) {
                    // 70%の確率で休憩を1回作成 (お昼休憩を想定)
                    if ($faker->boolean(70)) {
                        $breakData = BreakModel::factory()->forAttendance($attendance)->make()->toArray();
                        if(!empty($breakData)){ // forAttendanceで空が返ることがあるため
                             BreakModel::create(array_merge(['attendance_id' => $attendance->id], $breakData));
                        }
                    }
                }
            }
        }
    }
}
