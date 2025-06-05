<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker; // 必要に応じて
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class UserAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のユーザーと基本の勤怠データを作成
        $this->user = User::factory()->create();
        $this->user->markEmailAsVerified(); // メール認証済みとする
        $this->actingAs($this->user);

        $workDate = Carbon::parse('2023-07-15'); // 固定の日付でテスト
        Carbon::setTestNow($workDate); // 現在時刻を固定 (影響は少ないが念のため)

        $this->attendance = Attendance::factory()->for($this->user)->create([
            'work_date' => $workDate->toDateString(),
            'clock_in_time' => $workDate->copy()->hour(9)->minute(15)->second(0),
            'clock_out_time' => $workDate->copy()->hour(18)->minute(30)->second(0),
            'note' => '本日の作業備考テスト', // データベースのカラム名は 'note'
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // テスト時刻の固定を解除
        parent::tearDown();
    }

    /**
     * @test
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     */
    public function user_name_is_displayed_correctly_on_detail_page()
    {
        $response = $this->get(route('attendances.show', $this->attendance->id));

        $response->assertStatus(200);
        $response->assertViewIs('attendances.show');
        $response->assertSeeText($this->user->name);
    }

    /**
     * @test
     * 勤怠詳細画面の「日付」が選択した日付になっている
     */
    public function work_date_is_displayed_correctly_on_detail_page()
    {
        $response = $this->get(route('attendances.show', $this->attendance->id));

        $response->assertStatus(200);
        // Bladeテンプレートでの表示形式に合わせてアサート
        $expectedDateString = Carbon::parse($this->attendance->work_date)->isoFormat('YYYY年 M月D日');
        $response->assertSee($expectedDateString, false); // HTMLエスケープなしで検索
    }

    /**
     * @test
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     */
    public function clock_in_and_out_times_are_displayed_correctly_on_detail_page()
    {
        $response = $this->get(route('attendances.show', $this->attendance->id));

        $response->assertStatus(200);

        // Blade の input value 属性に含まれることを確認
        $expectedClockIn = Carbon::parse($this->attendance->clock_in_time)->format('H:i');
        $expectedClockOut = Carbon::parse($this->attendance->clock_out_time)->format('H:i');

        $response->assertSee("name=\"clock_in_time\"", false); // HTMLエスケープなし
        $response->assertSee("value=\"{$expectedClockIn}\"", false);

        $response->assertSee("name=\"clock_out_time\"", false);
        $response->assertSee("value=\"{$expectedClockOut}\"", false);
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している (休憩1回)
     */
    public function single_break_time_is_displayed_correctly_on_detail_page()
    {
        // 休憩データを1件作成
        $breakStart = Carbon::parse($this->attendance->work_date)->hour(12)->minute(0)->second(0);
        $breakEnd = Carbon::parse($this->attendance->work_date)->hour(13)->minute(0)->second(0);
        BreakModel::factory()->for($this->attendance)->create([
            'break_start_time' => $breakStart,
            'break_end_time' => $breakEnd,
        ]);

        $response = $this->get(route('attendances.show', $this->attendance->id));
        $response->assertStatus(200);

        $expectedBreakStart = $breakStart->format('H:i');
        $expectedBreakEnd = $breakEnd->format('H:i');

        // 最初の休憩入力欄 (name="break_start_time[]", name="break_end_time[]")
        // Blade側で $displayBreaks が渡され、それに基づいて表示される想定
        $response->assertSee("name=\"break_start_time[]\"", false);
        $response->assertSee("value=\"{$expectedBreakStart}\"", false);
        $response->assertSee("name=\"break_end_time[]\"", false);
        $response->assertSee("value=\"{$expectedBreakEnd}\"", false);
    }

    /**
     * @test
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している (休憩2回)
     */
    public function multiple_break_times_are_displayed_correctly_on_detail_page()
    {
        // 休憩データを2件作成
        $break1Start = Carbon::parse($this->attendance->work_date)->hour(12)->minute(0)->second(0);
        $break1End = Carbon::parse($this->attendance->work_date)->hour(12)->minute(45)->second(0);
        BreakModel::factory()->for($this->attendance)->create([
            'break_start_time' => $break1Start,
            'break_end_time' => $break1End,
        ]);

        $break2Start = Carbon::parse($this->attendance->work_date)->hour(15)->minute(0)->second(0);
        $break2End = Carbon::parse($this->attendance->work_date)->hour(15)->minute(15)->second(0);
        BreakModel::factory()->for($this->attendance)->create([
            'break_start_time' => $break2Start,
            'break_end_time' => $break2End,
        ]);

        $response = $this->get(route('attendances.show', $this->attendance->id));
        $response->assertStatus(200);

        $expectedBreak1Start = $break1Start->format('H:i');
        $expectedBreak1End = $break1End->format('H:i');
        $expectedBreak2Start = $break2Start->format('H:i');
        $expectedBreak2End = $break2End->format('H:i');

        // BladeテンプレートのHTML構造に依存してアサート
        // ここでは、value属性に期待する値が含まれているかで確認
        // 実際のHTML構造によっては、より詳細なDOMセレクタが必要になる場合がある
        $response->assertSeeInOrder([
            "value=\"{$expectedBreak1Start}\"",
            "value=\"{$expectedBreak1End}\"",
            "value=\"{$expectedBreak2Start}\"",
            "value=\"{$expectedBreak2End}\"",
        ], false); // false はHTMLエスケープしない
    }

    /**
     * @test
     * 備考が表示されることを確認
     */
    public function remarks_are_displayed_correctly_on_detail_page()
    {
        $response = $this->get(route('attendances.show', $this->attendance->id));
        $response->assertStatus(200);

        // textarea の中身を確認
        $response->assertSeeText($this->attendance->note); // note カラムの値を期待
    }
}
