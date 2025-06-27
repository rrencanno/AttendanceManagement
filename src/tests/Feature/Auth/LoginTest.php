<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => 'password123',
        ]);
    }

    /**
     * @test
     * メールアドレスが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function email_is_required_for_login()
    {
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * パスワードが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function password_is_required_for_login()
    {
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * 登録内容と一致しない場合（誤ったメールアドレス）に認証エラーが表示されることを確認
     */
    public function login_fails_with_incorrect_email()
    {
        $response = $this->post(route('login'), [
            'email' => 'wrong-email@example.com', // 存在しないメールアドレス
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * 登録内容と一致しない場合（誤ったパスワード）に認証エラーが表示されることを確認
     */
    public function login_fails_with_incorrect_password()
    {
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'wrong-password', // 誤ったパスワード
        ]);

        $response->assertSessionHasErrors('email'); // 認証失敗は通常 'email' キーに紐づく
    }


    /**
     * @test
     * 登録済みのユーザーが正しい情報でログインできることを確認
     */
    public function registered_user_can_login_with_correct_credentials()
    {
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => 'password123', // setUpで設定したパスワード
        ]);

        $this->assertAuthenticatedAs($this->user); // 指定したユーザーとして認証されたか

        $response->assertRedirect(config('fortify.home'));
    }

    /**
     * @test
     * ログインページが正しく表示されることを確認
     */
    public function login_page_can_be_rendered()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    /**
     * @test
     * 認証済みのユーザーはログインページにアクセスできない
     */
    public function authenticated_user_cannot_access_login_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(config('fortify.home'));
    }

    /**
     * @test
     * ログアウトできることを確認
     */
    public function user_can_logout()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }
}
