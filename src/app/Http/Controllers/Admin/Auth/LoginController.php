<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Admin\Auth\LoginRequest;

class LoginController extends Controller
{
    public function create()
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials, $request->boolean('remember'))) { // Auth::guard('admin') を削除
            // ログインに成功したら、is_admin フラグを確認
            if (Auth::user()->is_admin) {
                $request->session()->regenerate();
                return redirect()->intended(route('admin.attendances.list'));
            } else {
                // is_admin でないユーザーが管理者ログインしようとした場合
                Auth::logout(); // 一旦ログアウトさせる
                return back()->withErrors([
                    'email' => '管理者権限がありません。',
                ])->onlyInput('email');
            }
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout(); // Auth::guard('admin') を削除
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('admin.login.create');
    }
}
