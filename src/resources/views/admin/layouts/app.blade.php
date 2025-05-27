<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者画面 - CoachTech</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/common.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    @yield('css')
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                {{-- 管理者としてログインしている場合、管理者ダッシュボードや勤怠一覧へリンク --}}
                <a href="{{ route('admin.attendances.list') }}" class="logo-link"> {{-- 仮: 管理者勤怠一覧のルート名 --}}
                    <img src="{{ asset('storage/logo.svg') }}" alt="COACHTECHロゴ">
                </a>
            </div>

            {{-- 管理者としてログインしているかチェック --}}
            {{-- is_admin を使う場合は Auth::check() && Auth::user()->is_admin --}}
            {{-- もし admin ガードを使うなら @auth('admin') --}}
            @if (Auth::check() && Auth::user()->is_admin)
            <nav class="header-nav">
                <ul>
                    {{-- 各ルート名は admin.xxxx.yyyy のようなプレフィックスを付けると管理しやすい --}}
                    <li><a href="{{ route('admin.attendances.list') }}" class="nav-link {{ request()->routeIs('admin.attendances.list') ? 'active' : '' }}">勤怠一覧</a></li>
                    <li><a href="{{ route('admin.staff.list') }}" class="nav-link {{ request()->routeIs('admin.staff.list') ? 'active' : '' }}">スタッフ一覧</a></li>
                    <li><a href="{{ route('admin.correction_requests.index') }}" class="nav-link {{ request()->routeIs('admin.correction_requests.index*') ? 'active' : '' }}">申請一覧</a></li>
                    <li>
                        {{-- 管理者ログアウトのルートを指定 --}}
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="btn-logout">ログアウト</button>
                        </form>
                    </li>
                </ul>
            </nav>
            @endif
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    @stack('scripts') {{-- ページ個別のJavaScript用に @stack も用意しておくと便利 --}}
</body>
</html>