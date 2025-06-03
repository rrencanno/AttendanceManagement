<?php

namespace Tests\Feature\Admin\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User; // Userモデルを使用
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected $adminUser;
    protected $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト用の管理者ユーザーを作成
        $this->adminUser = User::factory()->create([
            'is_admin' => true,
            'password' => 'password123', // 平文のパスワード (モデル側でハッシュ化される想定)
        ]);

        // テスト用の一般ユーザーを作成 (管理者権限がないことをテストするため)
        $this->normalUser = User::factory()->create([
            'is_admin' => false,
            'password' => 'password123',
        ]);
    }

    /**
     * @test
     * 管理者ログイン画面が正しく表示されることを確認
     */
    public function admin_login_page_can_be_rendered()
    {
        $response = $this->get(route('admin.login.create'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.auth.login');
    }

    /**
     * @test
     * メールアドレスが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function email_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        // 具体的なメッセージは Admin/Auth/LoginRequest.php の messages() メソッドで定義
    }

    /**
     * @test
     * パスワードが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function password_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => $this->adminUser->email,
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * 登録内容と一致しない場合（誤ったメールアドレス）に認証エラーが表示されることを確認
     */
    public function admin_login_fails_with_incorrect_email()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => 'wrong-admin-email@example.com',
            'password' => 'password123',
        ]);

        // AdminLoginController@store のエラーメッセージに依存
        $response->assertSessionHasErrors('email');
        // エラーメッセージの文言を確認する場合
        // $errors = session('errors');
        // $this->assertEquals('メールアドレスまたはパスワードが正しくありません。', $errors->first('email'));
    }

    /**
     * @test
     * 登録内容と一致しない場合（誤ったパスワード）に認証エラーが表示されることを確認
     */
    public function admin_login_fails_with_incorrect_password()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => $this->adminUser->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * 一般ユーザーが管理者ログインページからログインしようとするとエラーになることを確認
     */
    public function normal_user_cannot_login_as_admin()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => $this->normalUser->email,
            'password' => 'password123', // 一般ユーザーの正しいパスワード
        ]);

        // AdminLoginController@store で '管理者権限がありません。' というエラーを返す想定
        $response->assertSessionHasErrors('email');
        // $errors = session('errors');
        // $this->assertEquals('管理者権限がありません。', $errors->first('email'));
        $this->assertGuest(); // ログインできていないことを確認
    }

    /**
     * @test
     * 管理者ユーザーが正しい情報でログインでき、管理者ページにリダイレクトされることを確認
     */
    public function admin_user_can_login_with_correct_credentials()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => $this->adminUser->email,
            'password' => 'password123', // setUpで設定したパスワードの平文
        ]);

        $this->assertAuthenticatedAs($this->adminUser);
        $response->assertRedirect(route('admin.attendances.list')); // 管理者用の勤怠一覧へ
    }

    /**
     * @test
     * 認証済みの管理者ユーザーは管理者ログインページにアクセスできない
     */
    public function authenticated_admin_user_cannot_access_admin_login_page()
    {
        $this->actingAs($this->adminUser); // 管理者としてログイン状態にする

        $response = $this->get(route('admin.login.create'));
        // guest ミドルウェアにより、管理者用ホームページ (例: admin.attendances.list) へリダイレクトされることを期待
        // 注意: RedirectIfAuthenticated ミドルウェアのリダイレクト先が管理者用になっているか確認
        $response->assertRedirect(route('admin.attendances.list'));
    }

    /**
     * @test
     * 管理者ユーザーがログアウトできることを確認
     */
    public function admin_user_can_logout()
    {
        $this->actingAs($this->adminUser);

        $response = $this->post(route('admin.logout')); // 管理者用ログアウトルート

        $this->assertGuest();
        $response->assertRedirect(route('admin.login.create')); // 管理者ログインページへリダイレクト
    }
}
