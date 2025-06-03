<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use Carbon\Carbon;

class BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    // ログインとメール認証済みのユーザーを作成し、出勤状態にするヘルパーメソッド
    private function createAndLoginClockedInUser(): array
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        // 出勤状態にする
        $attendance = Attendance::factory()->for($user)->create([
            'work_date' => Carbon::today()->toDateString(),
            'clock_in_time' => Carbon::now()->subHour(),
            'clock_out_time' => null,
        ]);
        return ['user' => $user, 'attendance' => $attendance];
    }

    /**
     * @test
     * 休憩開始ボタンが正しく機能し、ステータスが「休憩中」になることを確認
     */
    public function user_can_start_break_when_working()
    {
        ['user' => $user, 'attendance' => $attendance] = $this->createAndLoginClockedInUser();

        // 1. 画面に「休憩入」ボタンが表示されていることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('休憩入');

        // 2. 休憩開始処理を行う
        $response = $this->post(route('attendances.break.start'));

        // 3. データベースに休憩開始記録が作成されたことを確認
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => $attendance->id,
            // 'break_start_time' は now() なので厳密な比較は難しいが、存在を確認
            'break_end_time' => null, // 休憩終了はまだ
        ]);
        $latestBreak = BreakModel::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertNotNull($latestBreak->break_start_time);

        // 4. 勤怠打刻画面にリダイレクトされ、ステータスが「休憩中」になっていることを確認
        $response->assertRedirect(route('attendances.index'));
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('休憩中');
    }

    /**
     * @test
     * 休憩は一日に何回でもできる (休憩終了後、再度「休憩入」ボタンが表示される)
     */
    public function break_start_button_is_visible_after_ending_a_previous_break()
    {
        ['user' => $user, 'attendance' => $attendance] = $this->createAndLoginClockedInUser();

        // 1. 最初の休憩を開始し、終了する
        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subMinutes(30),
            'break_end_time' => Carbon::now()->subMinutes(15), // 既に終了
        ]);

        // 2. 画面に「休憩入」ボタンが再度表示されることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('休憩入'); // 休憩終了していれば、また休憩開始ボタンが出るはず
    }

    /**
     * @test
     * 休憩戻ボタンが正しく機能し、ステータスが「出勤中」に戻ることを確認
     */
    public function user_can_end_break_when_on_break()
    {
        ['user' => $user, 'attendance' => $attendance] = $this->createAndLoginClockedInUser();

        // 1. 休憩開始状態にする
        $break = BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subMinutes(30),
            'break_end_time' => null,
        ]);

        // 2. 画面に「休憩戻」ボタンが表示されていることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('休憩戻');

        // 3. 休憩終了処理を行う
        $response = $this->post(route('attendances.break.end'));

        // 4. データベースの休憩記録が更新されたことを確認
        $this->assertDatabaseHas('breaks', [
            'id' => $break->id,
            // 'break_end_time' が null でなくなったことを確認
        ]);
        $this->assertNotNull($break->fresh()->break_end_time);

        // 5. 勤怠打刻画面にリダイレクトされ、ステータスが「出勤中」に戻っていることを確認
        $response->assertRedirect(route('attendances.index'));
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('出勤中');
    }

    /**
     * @test
     * 休憩戻は一日に何回でもできる (再度休憩開始後、「休憩戻」ボタンが表示される)
     */
    public function break_end_button_is_visible_after_starting_a_new_break()
    {
        ['user' => $user, 'attendance' => $attendance] = $this->createAndLoginClockedInUser();

        // 1. 最初の休憩を開始し、終了
        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subHour(),
            'break_end_time' => Carbon::now()->subMinutes(45),
        ]);

        // 2. 再度休憩を開始する
        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => Carbon::now()->subMinutes(15), // 2回目の休憩開始
            'break_end_time' => null,
        ]);

        // 3. 画面に「休憩戻」ボタンが表示されることを確認
        $this->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('休憩戻');
    }


    /**
     * @test
     * 休憩時刻が勤怠一覧画面で確認できる
     * (これはユーザー側の勤怠一覧画面 /attendances/list での確認を想定)
     */
    public function break_times_are_visible_on_user_attendance_list_page()
    {
        ['user' => $user, 'attendance' => $attendance] = $this->createAndLoginClockedInUser();

        // 1. 休憩を開始し、終了する
        $breakStartTime = Carbon::parse($attendance->work_date . ' 12:00:00'); // 固定時刻で作成
        $breakEndTime = Carbon::parse($attendance->work_date . ' 13:00:00');   // 固定時刻で作成

        BreakModel::factory()->for($attendance)->create([
            'break_start_time' => $breakStartTime,
            'break_end_time' => $breakEndTime,
        ]);

        // 2. 退勤処理も行う (一覧画面で合計時間などが正しく表示されるため)
        $attendance->update(['clock_out_time' => Carbon::parse($attendance->work_date . ' 17:00:00')]);


        // 3. 勤怠一覧画面にアクセス
        $response = $this->get(route('attendances.list')); // ユーザーの勤怠一覧ルート

        $response->assertOk();

        // 表示されている月の勤怠記録に、期待する合計休憩時間が表示されているか確認
        // Attendanceモデルの getFormattedTotalBreakTimeAttribute アクセサが '01:00' を返すことを期待
        // 具体的な表示形式はBladeテンプレートに依存
        // ここでは、少なくとも「休憩」というヘッダーと、計算された休憩時間（例: 01:00）が含まれることを確認
        $response->assertSeeText('休憩'); // テーブルヘッダーなど
        // $response->assertSeeText($breakStartTime->format('H:i')); // 個別の休憩開始時刻 (もし表示していれば)
        // $response->assertSeeText($breakEndTime->format('H:i'));   // 個別の休憩終了時刻 (もし表示していれば)
        $response->assertSeeText('01:00'); // 合計休憩時間 (Attendanceモデルのアクセサの結果を期待)

        // 補足: このテストは Attendance モデルのアクセサや、
        // 勤怠一覧画面のBladeテンプレートの実装に依存します。
        // より堅牢なテストのためには、合計休憩時間の計算ロジック自体をユニットテストすることも有効です。
    }
}
