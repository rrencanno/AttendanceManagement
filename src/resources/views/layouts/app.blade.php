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
    <header>
        <div class="header-content">
            <div class="logo">
                {{-- ログイン状態に関わらずトップページ (または勤怠画面) へリンクすることを推奨 --}}
                <a href="{{ Auth::check() ? route('attendances.index') : url('/') }}" class="logo-link">
                    <img src="{{ asset('storage/logo.svg') }}" alt="COACHTECHロゴ">
                </a>
            </div>

            @auth {{-- ログインしている場合 --}}
            <nav class="header-nav">
                <ul>
                    <li><a href="{{ route('attendances.index') }}" class="nav-link">勤怠</a></li>
                    <li><a href="{{ route('attendances.list') }}" class="nav-link">勤怠一覧</a></li> {{-- 仮のルート名 --}}
                    <li><a href="{{ route('applications.index') }}" class="nav-link">申請</a></li> {{-- 仮のルート名 --}}
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn-logout">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
            @endauth
        </div>
    </header>

    <main>
        @yield('content')
    </main>

</body>

</html>