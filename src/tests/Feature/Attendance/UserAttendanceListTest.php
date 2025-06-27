<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class UserAttendanceListTest extends TestCase
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

    /**
     * @test
     * 自分の勤怠情報が全て（指定した月の範囲で）表示されることを確認
     */
    public function user_can_see_their_own_attendance_records_for_the_current_month()
    {
        $user = $this->createAndLoginVerifiedUser();
        $otherUser = User::factory()->create(); // 他のユーザーのデータが混入しないか確認用

        // 今月の勤怠データを3件作成
        $attendancesThisMonth = Attendance::factory()->count(3)->for($user)->create([
            'work_date' => Carbon::today()->subDays(rand(0, Carbon::today()->day -1)),
        ]);
        // 他のユーザーの今月の勤怠データ
        Attendance::factory()->for($otherUser)->create(['work_date' => Carbon::today()]);
        // 先月の勤怠データ
        Attendance::factory()->for($user)->create(['work_date' => Carbon::today()->subMonth()->startOfMonth()]);

        $response = $this->get(route('attendances.list'));

        $response->assertStatus(200);
        $response->assertViewIs('attendances.list');

        // 自分の今月の勤怠データが表示されていることを確認
        foreach ($attendancesThisMonth as $attendance) {
            $response->assertSeeText(Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)'));
        }
        // 他のユーザーの勤怠データが表示されていないことを確認
        $response->assertDontSeeText($otherUser->name);

        // 先月の勤怠データが表示されていないことを確認
        $response->assertDontSeeText(Carbon::today()->subMonth()->startOfMonth()->isoFormat('MM/DD(ddd)'));
    }

    /**
     * @test
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     */
    public function current_month_is_displayed_on_initial_load_of_attendance_list()
    {
        $this->createAndLoginVerifiedUser();

        $response = $this->get(route('attendances.list'));

        $response->assertStatus(200);
        $expectedMonthString = Carbon::now()->format('Y/m');
        $response->assertSeeText($expectedMonthString);
    }

    /**
     * @test
     * 「前月」ボタンを押下した時に表示月の前月の情報が表示される
     */
    public function previous_month_attendances_are_displayed_when_prev_month_button_is_clicked()
    {
        $user = $this->createAndLoginVerifiedUser();
        $today = Carbon::today();
        $prevMonth = $today->copy()->subMonth();

        // 今月のデータ
        Attendance::factory()->for($user)->create(['work_date' => $today->toDateString()]);
        // 先月のデータ
        $attendancePrevMonth = Attendance::factory()->for($user)->create(['work_date' => $prevMonth->toDateString()]);

        // まず当月表示
        $this->get(route('attendances.list'));

        // 「前月」リンクにアクセス
        $response = $this->get(route('attendances.list', ['month' => $prevMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSeeText($prevMonth->format('Y/m'));
        $response->assertSeeText(Carbon::parse($attendancePrevMonth->work_date)->isoFormat('MM/DD(ddd)')); // 先月のデータ
        $response->assertDontSeeText($today->isoFormat('MM/DD(ddd)')); // 今月のデータは表示されない
    }

    /**
     * @test
     * 「翌月」ボタンを押下した時に表示月の翌月の情報が表示される
     */
    public function next_month_attendances_are_displayed_when_next_month_button_is_clicked()
    {
        $user = $this->createAndLoginVerifiedUser();
        $today = Carbon::parse('2023-05-15');
        Carbon::setTestNow($today);

        $currentMonth = $today->copy();
        $nextMonth = $today->copy()->addMonth();

        // 今月のデータ
        Attendance::factory()->for($user)->create(['work_date' => $currentMonth->toDateString()]);
        // 翌月のデータ
        $attendanceNextMonth = Attendance::factory()->for($user)->create(['work_date' => $nextMonth->toDateString()]);

        // まず当月表示
        $this->get(route('attendances.list', ['month' => $currentMonth->format('Y-m')]));

        // 「翌月」リンクにアクセス
        $response = $this->get(route('attendances.list', ['month' => $nextMonth->format('Y-m')]));

        $response->assertStatus(200);
        $response->assertSeeText($nextMonth->format('Y/m'));
        $response->assertSeeText(Carbon::parse($attendanceNextMonth->work_date)->isoFormat('MM/DD(ddd)')); // 翌月のデータ
        $response->assertDontSeeText($currentMonth->isoFormat('MM/DD(ddd)')); // 今月のデータは表示されない

        Carbon::setTestNow(); // テスト時刻の固定を解除
    }

    /**
     * @test
     * 「詳細」ボタンを押下すると、その日の勤怠詳細画面に遷移する
     */
    public function user_can_navigate_to_attendance_detail_page()
    {
        $user = $this->createAndLoginVerifiedUser();
        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHour(),
        ]);

        // 勤怠一覧ページにアクセス
        $response = $this->get(route('attendances.list'));
        $response->assertStatus(200);
        $response->assertSee(route('attendances.show', $attendance->id));

        $detailResponse = $this->get(route('attendances.show', $attendance->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('attendances.show');

        $detailResponse->assertSeeText($user->name);
        $detailResponse->assertSee(Carbon::parse($attendance->work_date)->year);
        $detailResponse->assertSee(Carbon::parse($attendance->work_date)->month);
        $detailResponse->assertSee(Carbon::parse($attendance->work_date)->day);
    }
}
