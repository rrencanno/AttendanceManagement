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
                <a href="{{ route('admin.attendances.list') }}" class="logo-link">
                    <img src="{{ asset('storage/logo.svg') }}" alt="COACHTECHロゴ">
                </a>
            </div>

            @if (Auth::check() && Auth::user()->is_admin)
            <nav class="header-nav">
                <ul>
                    <li><a href="{{ route('admin.attendances.list') }}" class="nav-link {{ request()->routeIs('admin.attendances.list') ? 'active' : '' }}">勤怠一覧</a></li>
                    <li><a href="{{ route('admin.staff.list') }}" class="nav-link {{ request()->routeIs('admin.staff.list') ? 'active' : '' }}">スタッフ一覧</a></li>
                    <li><a href="{{ route('admin.correction_requests.index') }}" class="nav-link {{ request()->routeIs('admin.correction_requests.index*') ? 'active' : '' }}">申請一覧</a></li>
                    <li>
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

    @stack('scripts')
    
</body>
</html>