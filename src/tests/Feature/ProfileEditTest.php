<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_プロフィール編集ページに初期値が表示される()
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'テスト太郎',
            'image_url' => 'profiles/sample.jpg',
            'post_code' => '123-4567',
            'address' => '東京都渋谷区1-1-1',
        ]);

        $response = $this->actingAs($user)->get(route('mypage.profile'));

        $response->assertStatus(200);

        $response->assertSee('テスト太郎');
        $response->assertSee('123-4567');
        $response->assertSee('東京都渋谷区1-1-1');
        $response->assertSee('profiles/sample.jpg');
    }
}
