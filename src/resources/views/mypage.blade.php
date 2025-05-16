@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/mypage.css') }}">
@endsection

@section('content')
<div class="mypage-container">
    <div class="profile-section">
        <div class="profile-info">
            <img src="{{ asset('storage/' . ($user->image_url)) }}" class="profile-icon">
            <div class="profile-text">
                <h2>{{ $user->name }}</h2>
                <a href="{{ route('mypage.profile') }}" class="edit-profile-btn">プロフィールを編集</a>
            </div>
        </div>
    </div>

    <ul class="tabs">
        <li><a href="/mypage?tab=sell" class="{{ $tab === 'sell' ? 'active' : '' }}">出品した商品</a></li>
        <li><a href="/mypage?tab=buy" class="{{ $tab === 'buy' ? 'active' : '' }}">購入した商品</a></li>
    </ul>

    <div class="product-list">
        @foreach ($products as $product)
        <div class="product-item">
            @if (!empty($product->image_url))
                <img src="{{ asset('storage/' . $product->image_url) }}" alt="商品画像">
                <p class="product-name">{{ $product->name }}</p>
            @endif
        </div>
    @endforeach
    </div>

    <div class="pagination">
        {{ $products->appends(['tab' => $tab])->links() }}
    </div>
</div>
@endsection