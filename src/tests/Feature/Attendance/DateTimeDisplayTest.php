<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon; // Carbon を使用

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠打刻画面に現在の日付が正しい形式で表示されることを確認
     */
    public function current_date_is_displayed_correctly_on_attendance_page()
    {
        $user = User::factory()->create(); // メール認証済みユーザーを作成 (verifiedミドルウェア対策)
        $user->markEmailAsVerified(); // 明示的に認証済みにする

        $this->actingAs($user); // ログイン状態にする

        // テスト実行時の現在時刻を固定 (任意ですが、テストの再現性を高めるため)
        // Carbon::setTestNow(Carbon::create(2023, 6, 15, 10, 30, 0)); // 例: 2023年6月15日 10:30:00

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);

        // Carbon::setTestNow() を使う場合、その日付で期待値を生成
        // $expectedDateString = Carbon::getTestNow()->isoFormat('YYYY年M月D日(ddd)');
        // 使わない場合は、テスト実行時の現在の日付で期待値を生成
        $expectedDateString = Carbon::today()->isoFormat('YYYY年M月D日(ddd)');

        // Bladeビューに期待する日付文字列が含まれているか確認
        // 正規表現を使って曜日部分の揺れにも対応可能 (例: '(月)' や '(火)' など)
        // $response->assertSeeText($expectedDateString); // 完全一致
        $response->assertSeeTextInOrder([
            Carbon::today()->isoFormat('YYYY年M月D日'), // 年月日部分
            '('.Carbon::today()->isoFormat('ddd').')'  // (曜日) 部分
        ]);


        // Carbon::setTestNow(null); // テスト時刻の固定を解除
    }

    /**
     * @test
     * 勤怠打刻画面に時刻表示用の要素が存在することを確認
     */
    public function time_display_element_exists_on_attendance_page()
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();
        $this->actingAs($user);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);

        // JavaScriptで時刻を更新するためのidを持つ要素が存在するか確認
        $response->assertSee('id="currentTime"', false); // false はHTMLエスケープをしない検索
    }
}
