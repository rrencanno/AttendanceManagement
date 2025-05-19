@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email-page-container">
    <div class="verify-email-form-wrapper">
        <div class="verify-email-header">
            {{-- <h2>メール認証</h2> --}} {{-- 画像にはタイトルがないのでコメントアウト --}}
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success" role="alert">
                新しい認証メールがあなたのメールアドレスに送信されました。
            </div>
        @endif

        <div class="message-area">
            <p>ご登録いただいたメールアドレスに認証メールを送信しました。</p>
            <p>メール認証を完了してください。</p>
        </div>

        {{-- MailHogへのリンク (開発用) --}}
        @if(config('app.env') == 'local' && config('mail.host') == 'mailhog')
        <div class="form-group mailhog-link-button">
            <a href="http://localhost:8025" target="_blank" class="btn btn-mailhog">認証はこちらから (MailHog)</a>
        </div>
        @endif


        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <div class="form-group resend-button">
                <button type="submit" class="btn btn-link">認証メールを再送する</button>
            </div>
        </form>

        {{-- ログアウトフォーム (任意) --}}
        {{--
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <div class="form-group logout-button">
                <button type="submit" class="btn btn-link-logout">
                    ログアウト
                </button>
            </div>
        </form>
        --}}
    </div>
</div>
@endsection