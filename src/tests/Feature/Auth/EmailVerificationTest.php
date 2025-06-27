<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     * 会員登録後、認証メールが送信される
     */
    public function verification_email_is_sent_after_registration()
    {
        // Mailファサードをフェイクし、メールが実際に送信されないようにする
        Mail::fake();

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        // 1. 会員登録をする (POSTリクエスト)
        $response = $this->post(route('register'), $userData);

        // 2. 認証メールが送信されたことをアサート
        $user = User::whereEmail($userData['email'])->first();
        $this->assertNotNull($user, 'User was not created.');

        // Notificationファサードをフェイクして、メールの通知が送信される
        Notification::fake();

        event(new Registered($user));

        Notification::assertSentTo(
            [$user], // 通知の受信者
            VerifyEmail::class // 送信された通知
        );
    }

    /**
     * @test
     * メール認証誘導画面で「認証はこちらから」ボタン（MailHogリンク）が正しく表示される
     */
    public function mailhog_link_is_visible_on_verify_email_page_in_local_env()
    {
        // .env の APP_ENV と MAIL_HOST をテスト用に上書き
        config(['app.env' => 'local']);
        config(['mail.mailers.smtp.host' => 'mailhog']); // config('mail.host') が 'mailhog' になるように

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        // 1. メール認証誘導画面を表示する
        $response = $this->get(route('verification.notice'));

        $response->assertStatus(200);
        // 2. 「認証はこちらから」ボタンが表示されていることを確認
        $response->assertSeeText('認証はこちらから (MailHog)');
        $response->assertSee('href="http://localhost:8025"', false);
    }


    /**
     * @test
     * メール認証リンクをクリックすると、メールが認証され、勤怠画面にリダイレクトされる
     */
    public function email_can_be_verified_and_redirects_to_attendance_page()
    {
        $user = User::factory()->create([
            'email_verified_at' => null, // 未認証ユーザー
        ]);
        $this->actingAs($user);

        // 1. 認証URLを生成
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(config('auth.verification.expire', 60)), // 有効期限
            [ // ルートパラメータ
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // 2. 生成された認証URLにアクセス (メール認証を完了する)
        $response = $this->get($verificationUrl);

        // 3. ユーザーの email_verified_at が更新されたことを確認
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        // 4. 勤怠画面 (config('fortify.home')) にリダイレクトされることを確認
        $expectedRedirectUrl = url(config('fortify.home')) . '?verified=1';
        $response->assertRedirect($expectedRedirectUrl);
    }

    /**
     * @test
     * 既に認証済みのユーザーはメール認証画面にアクセスできない
     */
    public function verified_user_cannot_access_verify_email_page()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(), // 認証済みユーザー
        ]);
        $this->actingAs($user);

        $response = $this->get(route('verification.notice'));
        $response->assertRedirect(config('fortify.home')); // ホームにリダイレクト
    }
}
