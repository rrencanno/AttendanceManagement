@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/item_show.css') }}">
@endsection

@section('content')
<div class="item-detail">
    <div class="item-left">
        <img src="{{ asset('storage/' . $product->image_url) }}" alt="商品画像">
    </div>

    <div class="item-right">
        <h2>{{ $product->name }}</h2>
        <p class="brand">{{ $product->brand }}</p>
        <p class="price">&yen;{{ number_format($product->price) }} <span class="tax-in">(税込)</span></p>

        <div class="action-icons">
            <form action="{{ route('favorite.toggle', $product->id) }}" method="POST">
                @csrf
                <button type="submit" class="icon-btn {{ $product->isFavoritedBy(Auth::user()) ? 'favorited' : '' }}">
                    @if($product->isFavoritedBy(Auth::user()))
                        ★
                    @else
                        ☆
                    @endif
                </button>
                <span>{{ $product->favorites->count() }}</span>
            </form>
            <span>💬 {{ $product->comments->count() }}</span>
        </div>

        @auth
            <a href="{{ route('purchase.show', $product->id) }}" class="buy-btn">購入手続きへ</a>
        @else
            <div class="buy-hint">
                <a href="{{ route('login') }}" class="buy-btn">ログインして購入手続きへ</a>
                <p class="buy-hint-text">ログインすると購入手続きに進めます</p>
            </div>
        @endauth

        <div class="description">
            <h3>商品説明</h3>
            <p>{{ $product->description }}</p>
        </div>

        <div class="info">
            <h3>商品の情報</h3>
            <p>カテゴリー：
                @foreach($product->categories as $category)
                    <span class="tag">{{ $category->name }}</span>
                @endforeach
            </p>
            <p>商品の状態：{{ $product->condition }}</p>
        </div>

        <div class="comments">
            <h3>コメント({{ $product->comments->count() }})</h3>
            @foreach($product->comments as $comment)
                <div class="comment">
                    <img src="{{ $comment->user->image_url ? asset('storage/' . $comment->user->image_url) : 'https://via.placeholder.com/40' }}" alt="user" class="icon">
                    <div>
                        <p class="username">{{ $comment->user->name }}</p>
                        <p class="comment_body">{{ $comment->comment }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <form action="{{ route('comment.store', $product->id) }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="comment">商品へのコメント</label>
                <textarea name="comment" id="comment" rows="4">{{ old('comment') }}</textarea>
                @error('comment')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="comment-submit">コメントを送信する</button>
        </form>
    </div>
</div>
@endsection