<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class RegisteredUserController extends Controller
{
    /**
     * The action that creates new users.
     *
     * @var \Laravel\Fortify\Contracts\CreatesNewUsers
     */
    protected $creator;

    /**
     * Create a new controller instance.
     *
     * @param  \Laravel\Fortify\Contracts\CreatesNewUsers  $creator
     * @return void
     */
    public function __construct(CreatesNewUsers $creator)
    {
        $this->creator = $creator;
    }

    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \App\Http\Requests\RegisterRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request)
    {
        $user = $this->creator->create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect(config('fortify.home'));
    }
}
