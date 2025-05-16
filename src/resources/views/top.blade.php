@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/top.css') }}">
@endsection

@section('content')
<div class="container">
    <ul class="tabs">
        <li><a href="/?tab=recommend" class="{{ request('tab') !== 'mylist' ? 'active' : '' }}">おすすめ</a></li>
        <li><a href="/?tab=mylist" class="{{ request('tab') === 'mylist' ? 'active' : '' }}">マイリスト</a></li>
    </ul>

    <!-- 未ログイン時の画面 -->
    @if ($tab === 'mylist' && Auth::guest())
        <div class="info-message">
            <p>マイリストを表示するにはログインが必要です。</p>
            <a href="{{ route('login') }}" class="login-btn">ログイン</a>
        </div>
    @endif

    <!-- ログイン時の画面 -->
    <div class="product-list">
        @foreach ($products as $product)
        <div class="product-item">
            <div class="image-wrapper">
            @if($product->is_sold)
                    <img src="{{ asset('storage/' . $product->image_url) }}" alt="商品画像" class="sold-image">
                    <div class="overlay"></div>
                    <div class="sold-out">SOLD</div>
            @else
                <a href="{{ route('item_show', $product->id) }}" class="image-wrapper">
                    <img src="{{ asset('storage/' . $product->image_url) }}" alt="商品画像">
                </a>
            @endif
            </div>
            <p>{{ $product->name }}</p>
        </div>
        @endforeach
    </div>

    <div class="pagination">
            {{ $products->appends(request()->query())->links() }}
        </div>
</div>
@endsection