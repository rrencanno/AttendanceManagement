@extends('admin.layouts.app')

@section('title', '管理者ログイン')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/auth/login.css') }}">
@endsection

@section('content')
<div class="login-form-container">
    <h1 class="login-title">管理者ログイン</h1>

    <form method="POST" action="{{ route('admin.login.store') }}" class="login-form">
        @csrf
        <div class="form-group">
            <label for="email" class="form-label">メールアドレス</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autofocus class="form-input">
            @error('email') {{-- 認証失敗時のメッセージもここに表示される --}}
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group">
            <label for="password" class="form-label">パスワード</label>
            <input id="password" type="password" name="password" class="form-input">
            @error('password')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-login">
                管理者ログインする
            </button>
        </div>
    </form>
</div>
@endsection