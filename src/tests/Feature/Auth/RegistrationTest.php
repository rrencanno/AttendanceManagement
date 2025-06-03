<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegistrationTest extends TestCase
{
    use RefreshDatabase; // データベースをリフレッシュ
    use WithFaker;       // Faker を有効化

    /**
     * @test
     * 名前が未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function name_is_required_for_registration()
    {
        $response = $this->post(route('register'), [ // Fortifyの登録ルートを想定
            'name' => '', // 名前を空にする
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('name');
        // エラーメッセージの具体的な文言もテストしたい場合は、言語ファイルの設定に依存
        // Session::get('errors')->first('name') でメッセージを取得してアサート可能
        // 例: $this->assertEquals('お名前を入力してください。', Session::get('errors')->first('name'));
        // ただし、メッセージは変更される可能性があるので、キーの存在確認が無難
    }

    /**
     * @test
     * メールアドレスが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function email_is_required_for_registration()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => '', // メールアドレスを空にする
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * メールアドレスが不正な形式の場合にバリデーションエラーが表示されることを確認 (追加テスト)
     */
    public function email_must_be_a_valid_email_address()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => 'invalid-email', // 不正なメール形式
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * メールアドレスが既に存在する場合にバリデーションエラーが表示されることを確認 (追加テスト)
     */
    public function email_must_be_unique()
    {
        $existingUser = User::factory()->create(); // 既存ユーザーを作成

        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $existingUser->email, // 既存のメールアドレスを使用
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    /**
     * @test
     * パスワードが未入力の場合にバリデーションエラーが表示されることを確認
     */
    public function password_is_required_for_registration()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => '', // パスワードを空にする
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * パスワードが8文字未満の場合にバリデーションエラーが表示されることを確認
     */
    public function password_must_be_at_least_8_characters()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'short', // 8文字未満のパスワード
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors('password');
    }

    /**
     * @test
     * パスワードが一致しない場合にバリデーションエラーが表示されることを確認
     */
    public function passwords_must_match()
    {
        $response = $this->post(route('register'), [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'differentPassword', // 一致しない確認用パスワード
        ]);

        $response->assertSessionHasErrors('password');
        // 具体的なエラーメッセージキーは 'confirmed' (password_confirmation が password と一致しない場合)
        // $response->assertSessionHasErrors(['password' => 'パスワードとパスワード（確認用）が一致しません。']); // もし言語ファイルで設定したメッセージをテストする場合
    }


    /**
     * @test
     * フォームに内容が正常に入力された場合、データが保存され、ユーザーがリダイレクトされることを確認
     */
    public function user_can_register_with_valid_data()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register'), $userData);

        // データベースにユーザーが作成されたことを確認
        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
            // パスワードはハッシュ化されるため、直接比較はしない
        ]);

        // ユーザーが認証されたことを確認
        $this->assertAuthenticated();

        // 適切な場所にリダイレクトされることを確認
        // (Fortifyのデフォルトは config('fortify.home')、メール認証が有効なら /email/verify へ)
        // 今回は /email/verify へリダイレクトされることを期待
        $response->assertRedirect(config('fortify.home')); // または config('fortify.home')
    }

    /**
     * @test
     * 登録ページが正しく表示されることを確認 (追加テスト)
     */
    public function registration_page_can_be_rendered()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.register'); // Fortifyのデフォルトビュー名か、カスタマイズしたビュー名
    }
}
