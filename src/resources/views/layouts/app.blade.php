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
        <div class="logo">
            <a href="" class="logo-link">
                <img src="{{ asset('storage/logo.svg') }}" alt="COACHTECHロゴ">
            </a>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    
</body>

</html>