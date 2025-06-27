<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    // ログインとメール認証済みのユーザーを作成するヘルパーメソッド
    private function createAndLoginVerifiedUser(): User
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified(); // メール認証済みとする
        $this->actingAs($user);
        return $user;
    }

    /**
     * @test
     * 勤務外のユーザーが出勤ボタンを押し、正しく出勤処理が行われることを確認
     */
    public function user_can_clock_in_when_status_is_unstarted()
    {
        $user = $this->createAndLoginVerifiedUser();

        // 1. 勤怠打刻画面を開き、「出勤」ボタンが表示されていることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('出勤');

        // 2. 出勤処理を行う
        $response = $this->post(route('attendances.clockin'));

        // 3. データベースに出勤記録が作成されたことを確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => Carbon::today()->toDateString(),
        ]);
        $latestAttendance = Attendance::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($latestAttendance->clock_in_time);
        $this->assertNull($latestAttendance->clock_out_time);

        // 4. 勤怠打刻画面にリダイレクトされ、ステータスが変更されていることを確認
        $response->assertRedirect(route('attendances.index'));
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    /**
     * @test
     * 出勤は一日一回のみできる (退勤済みの場合、出勤ボタンは表示されない)
     */
    public function clock_in_button_is_not_visible_if_already_worked_today()
    {
        $user = $this->createAndLoginVerifiedUser();

        // 1. ステータスが退勤済であるユーザーのデータを作成
        Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(8),
            'clock_out_time' => Carbon::now()->subHour(),
        ]);

        // 2. 勤怠打刻画面を開き、出勤ボタンが表示されないことを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertDontSee('出勤');
    }

    /**
     * @test
     * 出勤は一日一回のみできる (既に出勤中の場合、再度出勤処理を試みるとエラーまたはリダイレクト)
     */
    public function user_cannot_clock_in_if_already_clocked_in_today_and_not_clocked_out()
    {
        $user = $this->createAndLoginVerifiedUser();

        // 既に出勤中のデータを作成
        Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHour(),
            'clock_out_time' => null,
        ]);

        // 再度出勤処理を試みる
        $response = $this->post(route('attendances.clockin'));

        // AttendanceController@clockIn で既に出勤済みの場合のリダイレクトとエラーメッセージを確認
        $response->assertRedirect(route('attendances.index'));
        $response->assertSessionHas('error', '既に出勤済みです。'); // コントローラーで設定したエラーメッセージ

        // データベースに出勤記録が重複して作成されていないことを確認
        $this->assertEquals(1, Attendance::where('user_id', $user->id)->where('work_date', Carbon::today()->toDateString())->count());
    }


    /**
     * @test
     * 出勤時刻がデータベースで確認できる
     */
    public function clock_in_time_is_recorded_in_database()
    {
        $user = $this->createAndLoginVerifiedUser();

        // テスト実行前の時刻を記録
        $timeBeforeClockIn = Carbon::now();

        // 出勤処理を行う
        $this->post(route('attendances.clockin'));

        // テスト実行後の時刻を記録
        $timeAfterClockIn = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
                                ->where('work_date', Carbon::today()->toDateString())
                                ->first();

        $this->assertNotNull($attendance, 'Attendance record not found.');
        $this->assertNotNull($attendance->clock_in_time, 'Clock-in time is not recorded.');

        // clock_in_time が Dateime 型であることを確認
        $this->assertInstanceOf(Carbon::class, Carbon::parse($attendance->clock_in_time));

        // 記録された出勤時刻が、処理実行時の時刻の範囲内にあることを確認 (数秒の誤差を許容)
        $clockInTimeCarbon = Carbon::parse($attendance->clock_in_time);
        $this->assertTrue(
            $clockInTimeCarbon->betweenIncluded($timeBeforeClockIn->copy()->subSeconds(5), $timeAfterClockIn->copy()->addSeconds(5)),
            "Clock-in time " . $clockInTimeCarbon->toDateTimeString() . " is not within the expected range between " .
            $timeBeforeClockIn->copy()->subSeconds(5)->toDateTimeString() . " and " . $timeAfterClockIn->copy()->addSeconds(5)->toDateTimeString()
        );
    }
}
