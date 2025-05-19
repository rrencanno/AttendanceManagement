@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="login-page-container">
    <div class="login-form-wrapper">
        <div class="login-header">
            <h2>ログイン</h2>
        </div>

        {{-- セッションに 'status' メッセージがあれば表示 (例: パスワードリセット成功後など) --}}
        @if (session('status'))
            <div class="alert alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" autofocus>
                @error('email') {{-- 認証失敗時のメッセージもここに表示される --}}
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                @error('password')
                    <span class="invalid-feedback" role="alert">
                        <strong>{{ $message }}</strong>
                    </span>
                @enderror
            </div>

            {{-- 「ログイン状態を記憶する」チェックボックス (任意) --}}
            {{--
            <div class="form-group form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    ログイン状態を記憶する
                </label>
            </div>
            --}}

            <div class="form-group">
                <button type="submit" class="btn-login">
                    ログインする
                </button>
            </div>
        </form>

        <div class="register-link">
            <a href="{{ route('register') }}">会員登録はこちら</a>
        </div>
        {{-- パスワードリセットへのリンク (任意) --}}
        {{--
        @if (Route::has('password.request'))
            <div class="forgot-password-link">
                <a href="{{ route('password.request') }}">
                    パスワードをお忘れですか？
                </a>
            </div>
        @endif
        --}}
    </div>
</div>
@endsection