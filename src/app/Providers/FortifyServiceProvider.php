<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginViewResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Fortify::createUsersUsing(CreateNewUser::class);

        Fortify::loginView(function () {
            return view('auth.login');
        });

        // 他のビューの定義 (登録画面など) も同様に設定できます
        Fortify::registerView(function () {
            return view('auth.register');
        });

        // メール認証が必要な場合の通知ビュー
        Fortify::verifyEmailView(function () {
            return view('auth.verify-email'); // 後で作成するビュー
        });


        // 他のFortifyアクションのバインドやビュー設定...
        // Fortify::requestPasswordResetLinkView(...);
        // Fortify::resetPasswordView(...);
        // Fortify::confirmPasswordView(...);
        // Fortify::twoFactorChallengeView(...);

        $this->app->singleton(
            \Laravel\Fortify\Contracts\LogoutResponse::class,
            \App\Http\Responses\CustomLogoutResponse::class // 後で作成します
        );
    }
}
