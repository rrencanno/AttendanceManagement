<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    // ログインとメール認証済みのユーザーを作成するヘルパーメソッド
    private function createAndLoginVerifiedUser(): User
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);
        return $user;
    }

    // 出勤状態のユーザーと勤怠記録を作成するヘルパーメソッド
    private function createClockedInAttendance(User $user): Attendance
    {
        return Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHour(), // 1時間前に出勤
            'clock_out_time' => null,
        ]);
    }

    /**
     * @test
     * 勤務中のユーザーが退勤ボタンを押し、正しく退勤処理が行われることを確認
     */
    public function user_can_clock_out_when_working()
    {
        $user = $this->createAndLoginVerifiedUser();
        $attendance = $this->createClockedInAttendance($user);

        // 1. 画面に「退勤」ボタンが表示されていることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('退勤'); // ボタンのテキストを確認

        // 2. 退勤処理を行う
        $response = $this->post(route('attendances.clockout'));

        // 3. データベースの勤怠記録が更新されたことを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            // 'clock_out_time' が null でなくなったことを確認
        ]);
        $this->assertNotNull($attendance->fresh()->clock_out_time);

        // 4. 勤怠打刻画面にリダイレクトされ、ステータスが変更されていることを確認
        $response->assertRedirect(route('attendances.index'));
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSeeText('退勤済')
            ->assertSeeText('お疲れ様でした。');
    }

    /**
     * @test
     * 休憩中に退勤しようとするとエラーメッセージが表示され、退勤できないことを確認
     * (AttendanceController@clockOut のロジックに依存)
     */
    public function user_cannot_clock_out_if_on_break()
    {
        $user = $this->createAndLoginVerifiedUser();
        $attendance = $this->createClockedInAttendance($user);

        // 休憩中のデータを作成
        $attendance->breaks()->create([
            'break_start_time' => Carbon::now()->subMinutes(30),
            'break_end_time' => null,
        ]);

        // 退勤処理を試みる
        $response = $this->post(route('attendances.clockout'));

        // AttendanceController@clockOut で休憩中の場合にエラーメッセージと共にリダイレクトされることを確認
        $response->assertRedirect(route('attendances.index'));
        $response->assertSessionHas('error', '休憩中です。先に休憩を終了してください。'); // コントローラーのエラーメッセージを確認

        // 退勤時刻が記録されていないことを確認
        $this->assertNull($attendance->fresh()->clock_out_time);
    }


    /**
     * @test
     * 退勤時刻がデータベースで確認できる
     */
    public function clock_out_time_is_recorded_in_database_after_clock_out()
    {
        $user = $this->createAndLoginVerifiedUser();

        // 1. 出勤処理を行う
        $this->post(route('attendances.clockin'));
        $attendance = Attendance::where('user_id', $user->id)->latest()->first();

        // テスト実行前の時刻を記録
        $timeBeforeClockOut = Carbon::now();

        // 2. 退勤処理を行う
        $this->post(route('attendances.clockout'));

        // テスト実行後の時刻を記録
        $timeAfterClockOut = Carbon::now();

        // データベースから最新の勤怠記録を再取得
        $updatedAttendance = $attendance->fresh();

        $this->assertNotNull($updatedAttendance, 'Attendance record not found after clock out.');
        $this->assertNotNull($updatedAttendance->clock_out_time, 'Clock-out time is not recorded.');

        // clock_out_time が Carbon インスタンスとして解釈できることを確認
        $this->assertInstanceOf(Carbon::class, Carbon::parse($updatedAttendance->clock_out_time));

        // 記録された退勤時刻が、処理実行時の時刻の範囲内にあることを確認 (数秒の誤差を許容)
        $clockOutTimeCarbon = Carbon::parse($updatedAttendance->clock_out_time);
        $this->assertTrue(
            $clockOutTimeCarbon->betweenIncluded($timeBeforeClockOut->copy()->subSeconds(5), $timeAfterClockOut->copy()->addSeconds(5)),
            "Clock-out time " . $clockOutTimeCarbon->toDateTimeString() . " is not within the expected range between " .
            $timeBeforeClockOut->copy()->subSeconds(5)->toDateTimeString() . " and " . $timeAfterClockOut->copy()->addSeconds(5)->toDateTimeString()
        );
    }

    /**
     * @test
     * 未出勤のユーザーは退勤できない (ボタンが表示されない、または処理が失敗する)
     */
    public function user_cannot_clock_out_if_not_clocked_in()
    {
        $this->createAndLoginVerifiedUser(); // 出勤データは作成しない

        // 画面に「退勤」ボタンが表示されないことを確認 (Bladeのロジックによる)
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertDontSee('退勤');

        // (オプション) 直接退勤処理を試みた場合のエラーハンドリングもテスト
        $response = $this->post(route('attendances.clockout'));
        $response->assertRedirect(route('attendances.index')); // リダイレクトされる
        $response->assertSessionHas('error'); // 何らかのエラーメッセージがあることを期待
    }
}
