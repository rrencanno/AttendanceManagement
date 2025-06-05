<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker; // 必要に応じて
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakModel;
use App\Models\AttendanceCorrectionRequest; // 申請モデル
use Carbon\Carbon;

class UserAttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Attendance $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->user->markEmailAsVerified();
        $this->actingAs($this->user);

        $workDate = Carbon::parse('2023-08-10');
        Carbon::setTestNow($workDate->copy()->hour(9)); // テスト時刻を固定

        $this->attendance = Attendance::factory()->for($this->user)->create([
            'work_date' => $workDate->toDateString(),
            'clock_in_time' => $workDate->copy()->hour(9)->minute(0),
            'clock_out_time' => $workDate->copy()->hour(18)->minute(0),
            'note' => '元の備考',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // 修正申請データを準備するヘルパー
    private function validCorrectionData(array $overrides = []): array
    {
        return array_merge([
            'clock_in_time' => '09:00',
            'clock_out_time' => '18:00',
            'break_start_time' => ['12:00'],
            'break_end_time' => ['13:00'],
            'requested_note' => '修正申請の理由です。',
        ], $overrides);
    }

    /**
     * @test
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function clock_in_time_after_clock_out_time_returns_validation_error()
    {
        $response = $this->post(route('attendances.request_correction', $this->attendance->id), $this->validCorrectionData([
            'clock_in_time' => '19:00', // 退勤時間(18:00)より後
            'clock_out_time' => '18:00',
        ]));

        $response->assertSessionHasErrors('clock_out_time'); // FormRequestのカスタムルールで clock_out_time にエラーが出る
        // $response->assertSessionHasErrors(['clock_out_time' => '出勤時間もしくは退勤時間が不適切な値です。']); // メッセージ確認
    }

    /**
     * @test
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function break_start_time_after_clock_out_time_returns_validation_error()
    {
        $response = $this->post(route('attendances.request_correction', $this->attendance->id), $this->validCorrectionData([
            'clock_out_time' => '17:00',
            'break_start_time' => ['18:00'], // 退勤時間(17:00)より後
            'break_end_time' => ['19:00'],
        ]));
        // FormRequest の break_start_time.* のカスタムルールで設定したメッセージを期待
        $response->assertSessionHasErrors('break_start_time.0');
        // $errors = session('errors')->get('break_start_time.0');
        // $this->assertContains('休憩時間が勤務時間外です。', $errors);
    }

    /**
     * @test
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     */
    public function break_end_time_after_clock_out_time_returns_validation_error()
    {
        $response = $this->post(route('attendances.request_correction', $this->attendance->id), $this->validCorrectionData([
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
    public function requested_note_is_required()
    {
        $response = $this->post(route('attendances.request_correction', $this->attendance->id), $this->validCorrectionData([
            'requested_note' => '', // 備考を空にする
        ]));

        $response->assertSessionHasErrors('requested_note');
        // $response->assertSessionHasErrors(['requested_note' => '備考を記入してください。']);
    }

    /**
     * @test
     * 有効なデータで勤怠修正申請が作成され、申請一覧で確認できる
     */
    public function valid_correction_request_can_be_submitted_and_seen_in_pending_list()
    {
        $correctionData = $this->validCorrectionData([
            'clock_in_time' => '09:30',
            'clock_out_time' => '18:30',
            'requested_note' => '電車遅延のため修正申請します。',
        ]);

        // 1. 修正申請処理を行う
        $response = $this->post(route('attendances.request_correction', $this->attendance->id), $correctionData);

        // 2. 申請がDBに作成されたことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'attendance_id' => $this->attendance->id,
            'user_id' => $this->user->id,
            'requested_note' => $correctionData['requested_note'],
            'status' => 'pending',
            // 時刻はDB保存時に Carbon インスタンスに変換されるので、文字列比較は注意
        ]);
        $createdRequest = AttendanceCorrectionRequest::latest()->first();
        $workDateString = Carbon::parse($this->attendance->work_date)->toDateString(); // 念のため work_date から日付文字列を取得
        $this->assertEquals(
            Carbon::parse($workDateString . ' ' . $correctionData['clock_in_time'])->toDateTimeString(),
            Carbon::parse($createdRequest->requested_clock_in_time)->toDateTimeString()
        );

        // 3. 勤怠詳細画面にリダイレクトされ、ステータスメッセージが表示されることを確認
        $response->assertRedirect(route('attendances.show', $this->attendance->id));
        $response->assertSessionHas('status', '勤怠修正を申請しました。');

        // 4. 申請一覧画面を開き、「承認待ち」に今作成した申請が表示されていることを確認
        // (ユーザー側の申請一覧画面を想定)
        $listResponse = $this->get(route('correction_requests.index', ['status' => 'pending']));
        $listResponse->assertOk();
        $listResponse->assertSeeText($correctionData['requested_note']); // 申請理由が表示されているか
        $listResponse->assertSeeText(Carbon::parse($this->attendance->work_date)->isoFormat('YYYY/MM/DD')); // 対象日時
    }

    /**
     * @test
     * 「承認済み」に管理者が承認した修正申請が表示される
     * (このテストは管理者の承認アクションをシミュレートする必要があり複雑。
     *  ここでは、DBで承認済みに変更して表示されるかを確認する簡易版)
     */
    public function approved_correction_request_is_shown_in_approved_list()
    {
        // 1. 申請データを作成
        $requestRecord = AttendanceCorrectionRequest::factory()
            ->for($this->attendance)
            ->for($this->user)
            ->create([
                'status' => 'approved',
                'requested_note' => 'テスト申請（承認済み確認用）',
            ]);

        // 2. 申請一覧画面（承認済みタブ）を開く
        $response = $this->get(route('correction_requests.index', ['status' => 'approved']));

        $response->assertOk();
        // 期待するテキストが指定した「テスト申請（承認済み確認用）」であることを確認
        $response->assertSeeText('テスト申請（承認済み確認用）');
        $response->assertSeeText('承認済み');
    }

    /**
     * @test
     * 各申請の「詳細」を押下すると申請詳細画面（ユーザーの勤怠詳細画面）に遷移する
     * (この要件はユーザー側の申請一覧から勤怠詳細に戻る動きを指していると解釈)
     */
    public function clicking_detail_on_correction_request_list_goes_to_attendance_detail()
    {
        // 1. 申請データを作成
        $requestRecord = AttendanceCorrectionRequest::factory()
            ->for($this->attendance)
            ->for($this->user)
            ->create([
                'status' => 'pending',
            ]);

        // 2. 申請一覧画面を開く
        $response = $this->get(route('correction_requests.index', ['status' => 'pending']));
        $response->assertOk();

        // 3. 「詳細」ボタンのリンク先が正しい勤怠詳細画面であることを確認し、遷移する
        // BladeテンプレートのHTML構造に依存するため、assertSeeでリンクの存在を確認する方が良い場合もある
        $response->assertSee(route('attendances.show', $this->attendance->id));

        // 実際に遷移してみる
        $detailResponse = $this->get(route('attendances.show', $this->attendance->id));
        $detailResponse->assertOk();
        $detailResponse->assertViewIs('attendances.show'); // 勤怠詳細画面のビュー
        // (オプション) 申請状況が画面に反映されているか（例：「承認待ちのため修正できません」）
        // $detailResponse->assertSeeText('承認待ちのため修正できません');
    }
}
