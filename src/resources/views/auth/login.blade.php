@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
    <h2>ログイン</h2>
    <form method="POST" action="{{ route('login') }}" class="login-form">
        @csrf
        <label for="email">メールアドレス</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}">
        @error('email')
            <p class="error">{{ $message }}</p>
        @enderror

        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" value="{{ old('password') }}">
        @error('password')
            <p class="error">{{ $message }}</p>
        @enderror

        @if ($errors->has('login_error'))
            <p class="error">{{ $errors->first('login_error') }}</p>
        @endif

        <button type="submit">ログインする</button>

        <a href="{{ route('register') }}" class="register-link">会員登録はこちら</a>
    </form>
@endsection