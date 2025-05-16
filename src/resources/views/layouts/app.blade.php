<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CoachTech</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @yield('css')
</head>
<body>
    <!-- POST送信かつ、見た目をリンクっぽくするため -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <header>
        <div class="logo">
            <a href="{{ route('top') }}" class="logo-link">
                <img src="{{ asset('storage/logo.svg') }}" alt="COACHTECHロゴ">
            </a>
        </div>

        <!-- ログイン or 会員登録画面のときはナビゲーション非表示 -->
        @unless (Route::is('login') || Route::is('register') || Str::startsWith(Route::currentRouteName(), 'register.'))
        <div class="header-nav">
            <form method="GET" action="{{ route('top') }}" class="search-form">
                <input type="hidden" name="tab" value="{{ request('tab', 'recommend') }}">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="なにをお探しですか？" class="search-input">
                <button type="submit" class="search-button">検索</button>
            </form>
            <!-- ✅ JavaScriptでPOST送信 -->
            <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                ログアウト
            </a>
            <a href="{{ route('mypage') }}" class="nav-link">マイページ</a>
            <a href="{{ route('sell') }}" class="nav-link">出品</a>
        </div>
        @endunless
    </header>

    <main>
        @yield('content')
    </main>

    @yield('js')
</body>

</html>