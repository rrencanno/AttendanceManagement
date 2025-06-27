<?php

namespace Tests\Feature\Attendance;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;

class DateTimeDisplayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * 勤怠打刻画面に現在の日付が正しい形式で表示されることを確認
     */
    public function current_date_is_displayed_correctly_on_attendance_page()
    {
        $user = User::factory()->create();
        $user->markEmailAsVerified();

        $this->actingAs($user);

        $response = $this->get(route('attendances.index'));

        $response->assertStatus(200);

        $expectedDateString = Carbon::today()->isoFormat('YYYY年M月D日(ddd)');

        $response->assertSeeTextInOrder([
            Carbon::today()->isoFormat('YYYY年M月D日'),
            '('.Carbon::today()->isoFormat('ddd').')'
        ]);
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
        $response->assertSee('id="currentTime"', false); // HTMLエスケープなしで検索
    }
}
