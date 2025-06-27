<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser1;
    private User $staffUser2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);

        $this->staffUser1 = User::factory()->create(['name' => '山田 太郎', 'email' => 'taro@example.com']);
        $this->staffUser2 = User::factory()->create(['name' => '佐藤 花子', 'email' => 'hanako@example.com']);
    }

    /**
     * @test
     * 管理者ユーザーがスタッフ一覧ページで全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function admin_can_view_all_staff_members_with_name_and_email()
    {
        $response = $this->get(route('admin.staff.list'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.staff.list');

        // 作成したスタッフの情報が表示されていることを確認
        $response->assertSeeText($this->staffUser1->name);
        $response->assertSeeText($this->staffUser1->email);
        $response->assertSeeText($this->staffUser2->name);
        $response->assertSeeText($this->staffUser2->email);

        // 管理者自身の情報は表示されないことを確認
        $response->assertDontSeeText($this->adminUser->name);
    }

    /**
     * @test
     * スタッフ一覧の「詳細」からスタッフ別勤怠一覧画面に遷移し、当月の勤怠が表示される
     */
    public function admin_can_view_specific_staff_attendance_list_for_current_month()
    {
        $targetMonth = Carbon::today();
        // スタッフ1の今月の勤怠データを作成
        $attendance = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $targetMonth->copy()->startOfMonth()->toDateString(),
            'clock_in_time' => $targetMonth->copy()->startOfMonth()->hour(9),
        ]);

        // スタッフ一覧の「詳細」リンクにアクセス
        $response = $this->get(route('admin.attendances.list_by_staff', [
            'user' => $this->staffUser1->id,
            'month' => $targetMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.attendances.list_by_staff');
        $response->assertSeeText($this->staffUser1->name . "さんの勤怠");
        $response->assertSeeText($targetMonth->format('Y / m'));
        $response->assertSeeText(Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)'));
    }

    /**
     * @test
     * スタッフ別勤怠一覧で「前月」ボタンを押下した時に前月の情報が表示される
     */
    public function previous_month_staff_attendances_are_displayed()
    {
        $currentMonth = Carbon::today();
        $prevMonth = $currentMonth->copy()->subMonth();

        // スタッフ1の先月の勤怠データ
        $attendancePrevMonth = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
        ]);
        // スタッフ1の今月の勤怠データ (表示されないはず)
        Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
        ]);

        // スタッフ1の先月の勤怠一覧にアクセス
        $response = $this->get(route('admin.attendances.list_by_staff', [
            'user' => $this->staffUser1->id,
            'month' => $prevMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertSeeText($prevMonth->format('Y / m'));
        $response->assertSeeText(Carbon::parse($attendancePrevMonth->work_date)->isoFormat('MM/DD(ddd)'));
        $response->assertDontSeeText($currentMonth->startOfMonth()->isoFormat('MM/DD(ddd)'));
    }

    /**
     * @test
     * スタッフ別勤怠一覧で「翌月」ボタンを押下した時に翌月の情報が表示される
     */
    public function next_month_staff_attendances_are_displayed()
    {
        $baseDate = Carbon::parse('2023-05-15');
        Carbon::setTestNow($baseDate);

        $currentMonth = $baseDate->copy();
        $nextMonth = $baseDate->copy()->addMonth();

        // スタッフ1の当月(2023-05)の勤怠データ
        Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $currentMonth->copy()->startOfMonth()->toDateString(),
        ]);
        // スタッフ1の翌月(2023-06)の勤怠データ
        $attendanceNextMonth = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $nextMonth->copy()->startOfMonth()->toDateString(),
        ]);

        // スタッフ1の翌月の勤怠一覧にアクセス
        $response = $this->get(route('admin.attendances.list_by_staff', [
            'user' => $this->staffUser1->id,
            'month' => $nextMonth->format('Y-m')
        ]));

        $response->assertStatus(200);
        $response->assertSeeText($nextMonth->format('Y / m'));
        $response->assertSeeText(Carbon::parse($attendanceNextMonth->work_date)->isoFormat('MM/DD(ddd)'));
        $response->assertDontSeeText($currentMonth->startOfMonth()->isoFormat('MM/DD(ddd)'));

        Carbon::setTestNow(); // テスト時刻の固定を解除
    }

    /**
     * @test
     * スタッフ別勤怠一覧の「詳細」を押下すると、その日の管理者用勤怠詳細画面に遷移する
     */
    public function admin_can_navigate_to_staff_attendance_detail_from_list_by_staff()
    {
        $targetDate = Carbon::today();
        // スタッフ1の今日の勤怠
        $attendance = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $targetDate->toDateString(),
            'clock_in_time' => $targetDate->copy()->hour(9),
        ]);

        // スタッフ1の当月の勤怠一覧にアクセス
        $response = $this->get(route('admin.attendances.list_by_staff', [
            'user' => $this->staffUser1->id,
            'month' => $targetDate->format('Y-m')
        ]));
        $response->assertStatus(200);

        // 「詳細」ボタンのリンクが存在することを確認
        $response->assertSee(route('admin.attendances.show', $attendance->id));

        // 実際に遷移して、管理者用詳細ページが表示されることを確認
        $detailResponse = $this->get(route('admin.attendances.show', $attendance->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('admin.attendances.show');
        $detailResponse->assertSeeText($this->staffUser1->name);
        $detailResponse->assertSee(Carbon::parse($attendance->work_date)->isoFormat('YYYY年 M月D日'), false);
    }
}
