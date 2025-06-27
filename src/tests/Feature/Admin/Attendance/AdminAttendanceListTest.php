<?php

namespace Tests\Feature\Admin\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser1;
    private User $staffUser2;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成しログイン
        $this->adminUser = User::factory()->admin()->create(); // admin state を使用
        $this->actingAs($this->adminUser);

        // 一般スタッフユーザーを2名作成
        $this->staffUser1 = User::factory()->create(['name' => '一般 一郎']);
        $this->staffUser2 = User::factory()->create(['name' => '一般 次郎']);
    }

    /**
     * @test
     * 管理者勤怠一覧画面に、指定した日の全ユーザーの勤怠情報が正確に表示される
     */
    public function admin_can_see_all_users_attendances_for_a_specific_date()
    {
        $targetDate = Carbon::today();

        // スタッフ1の今日の勤怠
        $attendance1 = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $targetDate->toDateString(),
            'clock_in_time' => $targetDate->copy()->hour(9)->minute(0),
            'clock_out_time' => $targetDate->copy()->hour(17)->minute(30),
        ]);
        // スタッフ2の今日の勤怠
        $attendance2 = Attendance::factory()->for($this->staffUser2)->create([
            'work_date' => $targetDate->toDateString(),
            'clock_in_time' => $targetDate->copy()->hour(10)->minute(0),
            'clock_out_time' => null, // 勤務中
        ]);
        // 昨日の勤怠 (表示されないはず)
        Attendance::factory()->for($this->staffUser1)->create(['work_date' => $targetDate->copy()->subDay()->toDateString()]);


        $response = $this->get(route('admin.attendances.list', ['date' => $targetDate->toDateString()]));

        $response->assertStatus(200);
        $response->assertViewIs('admin.attendances.list');

        // スタッフ1の情報が表示されている
        $response->assertSeeText($this->staffUser1->name);
        $response->assertSeeText(Carbon::parse($attendance1->clock_in_time)->format('H:i'));
        $response->assertSeeText(Carbon::parse($attendance1->clock_out_time)->format('H:i'));

        // スタッフ2の情報が表示されている
        $response->assertSeeText($this->staffUser2->name);
        $response->assertSeeText(Carbon::parse($attendance2->clock_in_time)->format('H:i'));
        $response->assertDontSeeText(Carbon::parse($targetDate->copy()->subDay()->toDateString())->isoFormat('MM/DD(ddd)')); // 昨日の日付は表示されない
    }

    /**
     * @test
     * 管理者勤怠一覧画面に遷移した際に現在の日付が表示される
     */
    public function current_date_is_displayed_on_initial_load_of_admin_attendance_list()
    {
        $response = $this->get(route('admin.attendances.list'));

        $response->assertStatus(200);

        $expectedDateString = Carbon::today()->isoFormat('YYYY年M月D日');
        $expectedNavDateString = Carbon::today()->toDateString();
        $response->assertSeeText($expectedDateString);
        $response->assertSee("value=\"{$expectedNavDateString}\"", false);
    }

    /**
     * @test
     * 「前日」ボタンを押下した時に前の日の勤怠情報が表示される
     */
    public function previous_day_attendances_are_displayed_when_prev_day_button_is_clicked()
    {
        $today = Carbon::today();
        $yesterday = $today->copy()->subDay();

        // 今日のスタッフ1の勤怠
        Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $today->toDateString(),
            'clock_in_time' => $today->copy()->hour(9),
        ]);
        // 昨日のスタッフ2の勤怠
        $attendanceYesterday = Attendance::factory()->for($this->staffUser2)->create([
            'work_date' => $yesterday->toDateString(),
            'clock_in_time' => $yesterday->copy()->hour(10),
        ]);

        // 「前日」リンクにアクセス
        $response = $this->get(route('admin.attendances.list', ['date' => $yesterday->toDateString()]));

        $response->assertStatus(200);
        $response->assertSeeText($yesterday->isoFormat('YYYY年M月D日')); // 前日の表示
        $response->assertSeeText($this->staffUser2->name); // 昨日のスタッフ2
        $response->assertSeeText(Carbon::parse($attendanceYesterday->clock_in_time)->format('H:i'));
        $response->assertDontSeeText($this->staffUser1->name); // 今日のスタッフ1は表示されない
    }

    /**
     * @test
     * 「翌日」ボタンを押下した時に次の日の勤怠情報が表示される
     */
    public function next_day_attendances_are_displayed_when_next_day_button_is_clicked()
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();

        // 今日のスタッフ1の勤怠
        Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $today->toDateString(),
            'clock_in_time' => $today->copy()->hour(9),
        ]);
        // 明日のスタッフ2の勤怠
        $attendanceTomorrow = Attendance::factory()->for($this->staffUser2)->create([
            'work_date' => $tomorrow->toDateString(),
            'clock_in_time' => $tomorrow->copy()->hour(10),
        ]);

        // 「翌日」リンクにアクセス
        $response = $this->get(route('admin.attendances.list', ['date' => $tomorrow->toDateString()]));

        $response->assertStatus(200);
        $response->assertSeeText($tomorrow->isoFormat('YYYY年M月D日')); // 翌日の表示
        $response->assertSeeText($this->staffUser2->name); // 明日のスタッフ2
        $response->assertSeeText(Carbon::parse($attendanceTomorrow->clock_in_time)->format('H:i'));
        $response->assertDontSeeText($this->staffUser1->name); // 今日のスタッフ1は表示されない
    }

    /**
     * @test
     * 管理者勤怠一覧の「詳細」を押下すると、その勤怠の管理者用詳細画面に遷移する
     */
    public function admin_can_navigate_to_admin_attendance_detail_page()
    {
        $targetDate = Carbon::today();
        $attendance = Attendance::factory()->for($this->staffUser1)->create([
            'work_date' => $targetDate->toDateString(),
            'clock_in_time' => $targetDate->copy()->hour(9),
        ]);

        $response = $this->get(route('admin.attendances.list', ['date' => $targetDate->toDateString()]));
        $response->assertStatus(200);

        // 詳細ボタンのリンクが存在することを確認
        $response->assertSee(route('admin.attendances.show', $attendance->id));

        // 実際に遷移して、管理者用詳細ページが表示されることを確認
        $detailResponse = $this->get(route('admin.attendances.show', $attendance->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertViewIs('admin.attendances.show');
        $detailResponse->assertSeeText($this->staffUser1->name);
        $expectedDateString = $targetDate->isoFormat('YYYY年 M月D日');
        $detailResponse->assertSee($expectedDateString, false); // HTMLエスケープなしで検索
    }
}
