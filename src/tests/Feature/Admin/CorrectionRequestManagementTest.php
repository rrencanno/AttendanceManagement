<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
// use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectionRequest;
use App\Models\BreakModel;
use Carbon\Carbon;

class CorrectionRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $staffUser;
    private Attendance $staffAttendance;
    private AttendanceCorrectionRequest $pendingRequest;
    private AttendanceCorrectionRequest $approvedRequest;

    protected function setUp(): void
    {
        parent::setUp();

        // 管理者ユーザーを作成しログイン
        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);

        // 一般スタッフユーザーを作成
        $this->staffUser = User::factory()->create(['name' => '申請 スタッフ']);

        // スタッフの勤怠データを作成
        $workDate = Carbon::parse('2023-10-05');
        $this->staffAttendance = Attendance::factory()->for($this->staffUser)->create([
            'work_date' => $workDate->toDateString(),
            'clock_in_time' => $workDate->copy()->hour(9)->minute(0),
            'clock_out_time' => $workDate->copy()->hour(18)->minute(0),
            'note' => '元の備考です',
        ]);

        // 承認待ちの申請データを作成
        $this->pendingRequest = AttendanceCorrectionRequest::factory()
            ->for($this->staffAttendance)
            ->for($this->staffUser, 'user')
            ->create([
                'requested_note' => '承認待ちの申請理由です。',
                'status' => 'pending',
                'requested_clock_in_time' => $workDate->copy()->hour(9)->minute(30),
            ]);

        // 承認済みの申請データを作成
        $anotherAttendance = Attendance::factory()->for($this->staffUser)->create([
            'work_date' => $workDate->copy()->subDay()->toDateString(), // 別の日
        ]);
        $this->approvedRequest = AttendanceCorrectionRequest::factory()
            ->for($anotherAttendance)
            ->for($this->staffUser, 'user')
            ->create([
                'requested_note' => '承認済みの申請理由です。',
                'status' => 'approved',
            ]);
    }

    /**
     * @test
     * 承認待ちの修正申請が全て（対象ページ分）表示されている
     */
    public function admin_can_view_all_pending_correction_requests()
    {
        $response = $this->get(route('admin.correction_requests.index', ['status' => 'pending']));

        $response->assertStatus(200);
        $response->assertViewIs('admin.correction_requests.index');
        $response->assertSeeText($this->pendingRequest->user->name); // 申請者名
        $response->assertSeeText($this->pendingRequest->requested_note);
        $response->assertSeeText('承認待ち');
        $response->assertDontSeeText($this->approvedRequest->requested_note); // 承認済みは表示されない
    }

    /**
     * @test
     * 承認済みの修正申請が全て（対象ページ分）表示されている
     */
    public function admin_can_view_all_approved_correction_requests()
    {
        $response = $this->get(route('admin.correction_requests.index', ['status' => 'approved']));

        $response->assertStatus(200);
        $response->assertViewIs('admin.correction_requests.index');
        $response->assertSeeText($this->approvedRequest->user->name);
        $response->assertSeeText($this->approvedRequest->requested_note);
        $response->assertSeeText('承認済み');
        $response->assertDontSeeText($this->pendingRequest->requested_note); // 承認待ちは表示されない
    }

    /**
     * @test
     * 修正申請の詳細内容が正しく表示されている（承認画面）
     */
    public function admin_can_view_correction_request_details_on_approval_page()
    {
        $response = $this->get(route('admin.correction_requests.show_approval_form', $this->pendingRequest->id));

        $response->assertStatus(200);
        $response->assertViewIs('admin.correction_requests.approve_form');

        $response->assertSeeText($this->pendingRequest->user->name); // 申請者名
        $response->assertSeeTextInOrder([
            Carbon::parse($this->pendingRequest->attendance->work_date)->isoFormat('YYYY年'),
            Carbon::parse($this->pendingRequest->attendance->work_date)->isoFormat('M月D日')
        ]);
        $response->assertSeeText($this->pendingRequest->requested_note); // 申請理由
        // 申請された出勤時刻 (H:i 形式で表示されていることを期待)
        $response->assertSeeText(Carbon::parse($this->pendingRequest->requested_clock_in_time)->format('H:i'));
    }

    /**
     * @test
     * 修正申請の承認処理が正しく行われ、データが更新され、リダイレクトされる
     */
    public function admin_can_approve_correction_request_and_data_is_updated()
    {
        $originalAttendance = $this->pendingRequest->attendance; // 更新前の勤怠記録
        $requestedNote = $this->pendingRequest->requested_note;
        $requestedClockIn = $this->pendingRequest->requested_clock_in_time; // Carbonインスタンスのはず
        $requestedClockOut = $this->pendingRequest->requested_clock_out_time; // nullかもしれない
        $requestedBreaks = $this->pendingRequest->requested_break_details; // これは配列のはず

        // 承認処理を実行
        $response = $this->post(route('admin.correction_requests.process', $this->pendingRequest->id), [
            'action' => 'approve',
        ]);

        // 1. リダイレクトと成功メッセージの確認
        $response->assertRedirect(route('admin.correction_requests.index', ['status' => 'approved']));
        $response->assertSessionHas('status');

        // 2. 申請レコードのステータスが 'approved' に更新されたことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $this->pendingRequest->id,
            'status' => 'approved',
        ]);

        // 3. 勤怠レコード (Attendance) が申請内容で更新されたことを確認
        $updatedAttendance = $originalAttendance->fresh();
        $this->assertEquals(
            Carbon::parse($requestedClockIn)->toDateTimeString(), // DBのdatetimeと比較
            Carbon::parse($updatedAttendance->clock_in_time)->toDateTimeString()
        );
        if ($requestedClockOut) {
            $this->assertEquals(
                Carbon::parse($requestedClockOut)->toDateTimeString(),
                Carbon::parse($updatedAttendance->clock_out_time)->toDateTimeString()
            );
        }
        $this->assertEquals($requestedNote, $updatedAttendance->note);

        // 4. 休憩レコード (BreakModel) が申請内容で更新されたことを確認
        $updatedAttendance->load('breaks');
        if (is_array($requestedBreaks)) {
            $this->assertCount(count(array_filter($requestedBreaks, fn($b) => !empty($b['start']) && !empty($b['end']))), $updatedAttendance->breaks);
            foreach ($requestedBreaks as $index => $reqBreak) {
                if (!empty($reqBreak['start']) && !empty($reqBreak['end'])) {
                    $dbBreak = $updatedAttendance->breaks[$index] ?? null;
                    $this->assertNotNull($dbBreak);
                    $this->assertEquals(
                        Carbon::parse($originalAttendance->work_date . ' ' . $reqBreak['start'])->toTimeString(),
                        Carbon::parse($dbBreak->break_start_time)->toTimeString()
                    );
                    $this->assertEquals(
                        Carbon::parse($originalAttendance->work_date . ' ' . $reqBreak['end'])->toTimeString(),
                        Carbon::parse($dbBreak->break_end_time)->toTimeString()
                    );
                }
            }
        }
    }
}
