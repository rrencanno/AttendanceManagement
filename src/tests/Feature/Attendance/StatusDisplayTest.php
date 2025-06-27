<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    // ログインとメール認証済みのユーザーを作成するヘルパーメソッド
    private function createAndLoginUser(): User
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);
        return $user;
    }

    /**
     * @test
     * 勤務外の場合、勤怠ステータスが「勤務外」と表示されることを確認
     */
    public function status_is_unstarted_when_not_clocked_in()
    {
        $this->createAndLoginUser();

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);
        $response->assertSeeText('勤務外');
    }

    /**
     * @test
     * 勤務中の場合、勤怠ステータスが「出勤中」と表示されることを確認
     */
    public function status_is_working_when_clocked_in_and_not_on_break_or_clocked_out()
    {
        $user = $this->createAndLoginUser();

        Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHour(),
            'clock_out_time' => null,
        ]);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }

    /**
     * @test
     * 休憩中の場合、勤怠ステータスが「休憩中」と表示されることを確認
     */
    public function status_is_on_break_when_break_started()
    {
        $user = $this->createAndLoginUser();

        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(2),
            'clock_out_time' => null,
        ]);

        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subHour(),
            'break_end_time' => null,
        ]);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);
        $response->assertSeeText('休憩中');
    }

    /**
     * @test
     * 退勤済みの場合、勤怠ステータスが「退勤済」と表示されることを確認
     */
    public function status_is_finished_today_when_clocked_out()
    {
        $user = $this->createAndLoginUser();

        Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(8),
            'clock_out_time' => Carbon::now()->subHour(),
        ]);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);
        $response->assertSeeText('退勤済');
        $response->assertSeeText('お疲れ様でした。');
    }

    /**
     * @test
     * 休憩終了後、勤務中に戻る場合、勤怠ステータスが「出勤中」と表示されることを確認
     */
    public function status_is_working_after_break_ended()
    {
        $user = $this->createAndLoginUser();

        // 出勤状態のデータを作成
        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHours(3), // 3時間前に出勤
            'clock_out_time' => null,
        ]);

        // 休憩終了状態のデータを作成
        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subHours(2), // 2時間前に休憩開始
            'break_end_time' => Carbon::now()->subHour(),   // 1時間前に休憩終了
        ]);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);
        $response->assertSeeText('出勤中');
    }
}
