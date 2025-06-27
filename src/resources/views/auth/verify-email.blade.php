@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify-email.css') }}">
@endsection

@section('content')
<div class="verify-email-page-container">
    <div class="verify-email-form-wrapper">
        @if (session('status') == 'verification-link-sent')
            <div class="alert alert-success" role="alert">
                新しい認証メールがあなたのメールアドレスに送信されました。
            </div>
        @endif

        <div class="message-area">
            <p>ご登録いただいたメールアドレスに認証メールを送信しました。</p>
            <p>メール認証を完了してください。</p>
        </div>

        @if(config('app.env') == 'local')
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
    </div>
</div>
@endsection