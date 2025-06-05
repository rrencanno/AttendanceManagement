<?php

namespace Tests\Feature\Admin\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private Attendance $staffAttendance;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成しログイン
        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);

        // 一般スタッフユーザーを作成
        $this->staffUser = User::factory()->create(['name' => 'テスト スタッフ']);

        // スタッフの勤怠データを作成
        $workDate = Carbon::parse('2023-09-20');
        Carbon::setTestNow($workDate->copy()->hour(10)); // テスト時刻を固定

        $this->staffAttendance = Attendance::factory()->for($this->staffUser)->create([
            'work_date' => $workDate->toDateString(),
            'clock_in_time' => $workDate->copy()->hour(9)->minute(5),
            'clock_out_time' => $workDate->copy()->hour(18)->minute(15),
            'note' => 'テスト用の初期備考',
        ]);

        // 休憩データも作成
        BreakModel::factory()->for($this->staffAttendance)->create([
            'break_start_time' => $workDate->copy()->hour(12)->minute(0),
            'break_end_time' => $workDate->copy()->hour(13)->minute(0),
        ]);
        BreakModel::factory()->for($this->staffAttendance)->create([
            'break_start_time' => $workDate->copy()->hour(15)->minute(0),
            'break_end_time' => $workDate->copy()->hour(15)->minute(15),
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 修正データを準備するヘルパー
    private function validUpdateData(array $overrides = []): array
    {
        // setUp で作成した勤怠データをベースに、有効な更新データを生成
        $baseData = [
            'clock_in_time' => Carbon::parse($this->staffAttendance->clock_in_time)->format('H:i'),
            'clock_out_time' => Carbon::parse($this->staffAttendance->clock_out_time)->format('H:i'),
            'break_start_time' => $this->staffAttendance->breaks->map(fn($b) => Carbon::parse($b->break_start_time)->format('H:i'))->toArray(),
            'break_end_time' => $this->staffAttendance->breaks->map(fn($b) => Carbon::parse($b->break_end_time)->format('H:i'))->toArray(),
            'remarks' => $this->staffAttendance->note,
        ];
        return array_merge($baseData, $overrides);
    }

    /**
     * @test
     * 勤怠詳細画面に選択した勤怠のデータが正しく表示される
     */
    public function attendance_details_are_displayed_correctly()
    {
        $response = $this->get(route('admin.attendances.show', $this->staffAttendance->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.attendances.show');

        // スタッフ名
        $response->assertSeeText($this->staffUser->name);
        // 日付 (表示形式に注意)
        $response->assertSee(Carbon::parse($this->staffAttendance->work_date)->isoFormat('YYYY年 M月D日'), false);
        // 出勤・退勤時間 (inputのvalueで確認)
        $response->assertSee("value=\"" . Carbon::parse($this->staffAttendance->clock_in_time)->format('H:i') . "\"", false);
        $response->assertSee("value=\"" . Carbon::parse($this->staffAttendance->clock_out_time)->format('H:i') . "\"", false);
        // 休憩時間 (inputのvalueで確認、複数ある場合も考慮)
        foreach ($this->staffAttendance->breaks as $break) {
            $response->assertSee("value=\"" . Carbon::parse($break->break_start_time)->format('H:i') . "\"", false);
            $response->assertSee("value=\"" . Carbon::parse($break->break_end_time)->format('H:i') . "\"", false);
        }
        // 備考 (textareaの中身で確認)
        $response->assertSeeText($this->staffAttendance->note);
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function clock_in_time_after_clock_out_time_returns_validation_error_on_update()
    {
        $response = $this->put(route('admin.attendances.update', $this->staffAttendance->id), $this->validUpdateData([
            'clock_in_time' => '19:00', // 退勤時間(18:15)より後
            'clock_out_time' => '18:15',
        ]));
        $response->assertSessionHasErrors('clock_out_time');
        // $errors = session('errors');
        // $this->assertStringContainsString('出勤時間もしくは退勤時間が不適切な値です。', $errors->first('clock_out_time'));
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function break_start_time_after_clock_out_time_returns_validation_error_on_update()
    {
        $response = $this->put(route('admin.attendances.update', $this->staffAttendance->id), $this->validUpdateData([
            'clock_out_time' => '17:00',
            'break_start_time' => ['18:00'], // 退勤時間(17:00)より後
            'break_end_time' => ['19:00'],   // 整合性のためこちらも設定
        ]));
        $response->assertSessionHasErrors('break_start_time.0');
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function break_end_time_after_clock_out_time_returns_validation_error_on_update()
    {
        $response = $this->put(route('admin.attendances.update', $this->staffAttendance->id), $this->validUpdateData([
            'clock_out_time' => '17:00',
            'break_start_time' => ['16:00'],
            'break_end_time' => ['18:00'], // 退勤時間(17:00)より後
        ]));
        $response->assertSessionHasErrors('break_end_time.0');
    }

    /**
     * @test
     * 備考欄が未入力の場合のエラーメッセージが表示される
     */
    public function remarks_is_required_on_update()
    {
        $response = $this->put(route('admin.attendances.update', $this->staffAttendance->id), $this->validUpdateData([
            'remarks' => '', // 備考を空にする
        ]));
        $response->assertSessionHasErrors('remarks');
        // $errors = session('errors');
        // $this->assertEquals('備考を記入してください。', $errors->first('remarks'));
    }

    /**
     * @test
     * 有効なデータで勤怠情報を正しく修正できる
     */
    public function admin_can_successfully_update_attendance_details()
    {
        $updateData = [
            'clock_in_time' => '08:30',
            'clock_out_time' => '17:45',
            'break_start_time' => ['12:15'], // 休憩は1回に減らす例
            'break_end_time' => ['13:00'],
            'remarks' => '管理者が修正しました。',
        ];

        $response = $this->put(route('admin.attendances.update', $this->staffAttendance->id), $updateData);

        $response->assertRedirect(route('admin.attendances.show', $this->staffAttendance->id));
        $response->assertSessionHas('status', '勤怠情報を更新しました。');

        // データベースの勤怠情報が更新されたことを確認
        $updatedAttendance = $this->staffAttendance->fresh(); // DBから最新情報を取得
        $this->assertEquals(
            Carbon::parse($updatedAttendance->work_date . ' ' . $updateData['clock_in_time'])->toDateTimeString(),
            Carbon::parse($updatedAttendance->clock_in_time)->toDateTimeString()
        );
        $this->assertEquals(
            Carbon::parse($updatedAttendance->work_date . ' ' . $updateData['clock_out_time'])->toDateTimeString(),
            Carbon::parse($updatedAttendance->clock_out_time)->toDateTimeString()
        );
        $this->assertEquals($updateData['remarks'], $updatedAttendance->note); // カラム名は note

        // データベースの休憩情報が更新されたことを確認 (1件になっているはず)
        $this->assertCount(1, $updatedAttendance->breaks);
        $this->assertEquals(
            Carbon::parse($updatedAttendance->work_date . ' ' . $updateData['break_start_time'][0])->toDateTimeString(),
            Carbon::parse($updatedAttendance->breaks[0]->break_start_time)->toDateTimeString()
        );
        $this->assertEquals(
            Carbon::parse($updatedAttendance->work_date . ' ' . $updateData['break_end_time'][0])->toDateTimeString(),
            Carbon::parse($updatedAttendance->breaks[0]->break_end_time)->toDateTimeString()
        );
    }
}
