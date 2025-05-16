<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfProfileIncomplete
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->profile_completed) {
            return redirect()->route('profile.edit');
        }
        return $next($request);
    }
}
