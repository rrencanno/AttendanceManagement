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

    // 各テストの前に実行されるセットアップメソッド
    protected function setUp(): void
    {
        parent::setUp();

        // テスト用のユーザーを事前に作成・保存
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
        $response = $this->post(route('login'), [ // Fortifyのログインルートを想定
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        // 具体的なメッセージをテストする場合 (言語ファイルに依存)
        // $errors = session('errors');
        // $this->assertEquals('メールアドレスを入力してください。', $errors->first('email'));
    }

    /**
     * @test
     * パスワードが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function password_is_required_for_login()
    {
        $response = $this->post(route('login'), [
            'email' => $this->user->email,
            'password' => '', // パスワードを空にする
        ]);

        $response->assertSessionHasErrors('password');
        // 具体的なメッセージをテストする場合 (言語ファイルに依存)
        // $errors = session('errors');
        // $this->assertEquals('パスワードを入力してください。', $errors->first('password'));
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

        // Fortifyのデフォルトでは、認証失敗は 'email' フィールドにエラーが紐づく
        $response->assertSessionHasErrors('email');
        // 具体的なメッセージをテストする場合 (言語ファイルに依存)
        // $errors = session('errors');
        // $this->assertEquals('ログイン情報が登録されていません。', $errors->first('email'));
    }

    /**
     * @test
     * 登録内容と一致しない場合（誤ったパスワード）に認証エラーが表示されることを確認 (追加テスト)
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
        // リダイレクト先は config('fortify.home') またはメール認証が有効なら verification.notice
        // メール認証済みのユーザーをテストで使う場合は、Factoryで email_verified_at を設定するか、
        // 登録テストと同様に verification.notice へのリダイレクトを期待する
        // ここでは、認証が成功し、意図した場所へリダイレクトされることを確認
        $response->assertRedirect(config('fortify.home'));
    }

    /**
     * @test
     * ログインページが正しく表示されることを確認 (追加テスト)
     */
    public function login_page_can_be_rendered()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.login'); // Fortifyのデフォルトビュー名か、カスタマイズしたビュー名
    }

    /**
     * @test
     * 認証済みのユーザーはログインページにアクセスできない (guestミドルウェアのテスト) (追加テスト)
     */
    public function authenticated_user_cannot_access_login_page()
    {
        // $this->actingAs($this->user); // setupで作成したユーザーとしてログイン状態にする
        // または
        $user = User::factory()->create(); // 別のユーザーを作成しても良い
        $this->actingAs($user);

        $response = $this->get(route('login'));
        $response->assertRedirect(config('fortify.home')); // ホームにリダイレクトされることを期待
    }

    /**
     * @test
     * ログアウトできることを確認 (追加テスト)
     */
    public function user_can_logout()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('logout'));

        $this->assertGuest(); // ログアウトしてゲスト状態になったか
        $response->assertRedirect(route('login'));
    }
}
