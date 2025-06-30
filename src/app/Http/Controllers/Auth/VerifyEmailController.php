<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\VerifyEmailResponse;
use Laravel\Fortify\Fortify;

class VerifyEmailController
{
    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Laravel\Fortify\Contracts\VerifyEmailResponse
     */
    public function __invoke(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            // 既に認証済みの場合のリダイレクト
            return $this->redirectBasedOnRole($request);
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        // 認証成功後のリダイレクト
        return $this->redirectBasedOnRole($request, true);
    }

    /**
     * ユーザーの役割に基づいてリダイレクト先を決定する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  bool  $withVerifiedQueryParam
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectBasedOnRole(Request $request, bool $withVerifiedQueryParam = false)
    {
        // もしユーザーが管理者なら
        if ($request->user()->is_admin) {
            $redirectUrl = route('admin.attendances.list'); // 管理者用勤怠一覧へ
        } else {
            // 一般ユーザーなら
            $redirectUrl = config('fortify.home'); // 設定ファイルで定義されたホームへ
        }

        // Fortifyのデフォルトの挙動に合わせて ?verified=1 を付ける
        if ($withVerifiedQueryParam) {
            $redirectUrl .= '?verified=1';
        }

        return redirect()->intended($redirectUrl);
    }
}
