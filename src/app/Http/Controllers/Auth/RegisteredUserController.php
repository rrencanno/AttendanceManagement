<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers; // Fortifyのユーザー作成契約

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
        return view('auth.register'); // 作成するビューのパス
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
        // RegisterRequestでバリデーションは実行されます。

        // FortifyのCreateNewUserアクションを呼び出してユーザーを作成
        // このアクションは app/Actions/Fortify/CreateNewUser.php にあります。
        // 必要であれば、このファイルを編集して is_admin フラグなどを調整できますが、
        // 今回は従業員登録なので、デフォルトで is_admin = false で問題ないでしょう。
        $user = $this->creator->create($request->validated());

        // 登録イベントを発行します。
        // これにより、MustVerifyEmailインターフェースをUserモデルが実装していれば、
        // 自動的にメール認証メールが送信されます。
        event(new Registered($user));

        // 作成されたユーザーでログインさせます。
        Auth::login($user);

        // 登録後のリダイレクト先。
        // Fortifyは通常、メール認証が必要な場合は /email/verify (verification.notice ルート) へ
        // リダイレクトします。それが設定されていれば、config('fortify.home') に従います。
        // ここではFortifyのリダイレクトロジックに任せるため、
        // Fortifyのデフォルトのホームリダイレクト先を指定します。
        return redirect(config('fortify.home'));
    }
}
