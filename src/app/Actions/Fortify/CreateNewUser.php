<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array  $input
     * @return \App\Models\User
     */
    public function create(array $input)
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([ // ← この行でユーザーを作成し、そのインスタンスを返しているか確認
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'], // Userモデルのミューテータでハッシュ化される
            // 'is_admin' => false, // ここで is_admin を設定することも可能 (デフォルトはマイグレーションで設定)
        ]);
    }
}
