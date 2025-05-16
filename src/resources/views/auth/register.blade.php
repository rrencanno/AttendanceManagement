@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
    <h2>会員登録</h2>

    <form method="POST" action="{{ route('register') }}" class="register-form">
        @csrf

        <div>
            <label for="name">ユーザー名</label>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
        </div>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror

        <div>
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}">
        </div>
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <div>
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password">
        </div>
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror

        <div>
            <label for="password_confirmation">確認用パスワード</label>
            <input type="password" id="password_confirmation" name="password_confirmation">
        </div>
        @error('password_confirmation')
            <p class="error">{{ $message }}</p>
        @enderror

        <button type="submit" class="register-button">登録する</button>
    </form>

    <a href="{{ route('login') }}" class="login-link">ログインはこちら</a>
@endsection
