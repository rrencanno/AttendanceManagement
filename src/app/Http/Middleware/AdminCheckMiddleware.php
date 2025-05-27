<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCheckMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->is_admin) {
            // 管理者でない場合、適切な場所にリダイレクトまたはエラーを返す
            // 例えば、一般ユーザーのトップページや、403エラーなど
            // return redirect('/');
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }
}
