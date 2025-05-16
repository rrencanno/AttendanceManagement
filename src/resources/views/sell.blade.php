@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/sell.css') }}">
@endsection

@section('content')
<div class="sell-container">
    <h2>商品の出品</h2>
    <form action="{{ route('sell.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        {{-- 商品画像 --}}
        <div class="form-section image-upload">
            <label>商品画像</label>
            <div class="upload-box">
                <label for="image-input" class="upload-label">画像を選択する</label>
                <input type="file" id="image-input" name="image" class="hidden-input" accept="image/*">
                <div class="image-preview">
                    <img id="preview" src="#" alt="プレビュー画像" style="display: none; max-width: 100%; margin-top: 10px;">
                </div>
            </div>
        </div>
        @error('image')
            <p class="error">{{ $message }}</p>
        @enderror

        {{-- カテゴリー --}}
        <div class="form-section">
            <label>カテゴリー</label>
            <div class="category-buttons">
                @foreach ([
                    'ファッション', '家電', 'インテリア', 'レディース', 'メンズ', 'コスメ', '本',
                    'ゲーム', 'スポーツ', 'キッチン', 'ハンドメイド', 'アクセサリー', 'おもちゃ', 'ベビー・キッズ'
                ] as $category)
                    <label class="category-label">
                        <input type="checkbox" name="categories[]" value="{{ $category }}">
                        <span>{{ $category }}</span>
                    </label>
                @endforeach
            </div>
        </div>
        @error('categories')
            <p class="error">{{ $message }}</p>
        @enderror

        <div class="form-section">
            <label>商品の状態</label>
            <select name="condition">
                <option value="">選択してください</option>
                <option value="良好">良好</option>
                <option value="目立った傷や汚れなし">目立った傷や汚れなし</option>
                <option value="やや傷や汚れあり">やや傷や汚れあり</option>
                <option value="状態が悪い">状態が悪い</option>
            </select>
        </div>
        @error('condition')
            <p class="error">{{ $message }}</p>
        @enderror

        <div class="form-section">
            <label>商品名</label>
            <input type="text" name="name">
        </div>
        @error('name')
            <p class="error">{{ $message }}</p>
        @enderror

        <div class="form-section">
            <label>ブランド名</label>
            <input type="text" name="brand">
        </div>
        @error('brand')
            <p class="error">{{ $message }}</p>
        @enderror

        <div class="form-section">
            <label>商品の説明</label>
            <textarea name="description" rows="4"></textarea>
        </div>
        @error('description')
            <p class="error">{{ $message }}</p>
        @enderror

        <div class="form-section">
            <label>販売価格</label>
            <div class="price-input-wrapper">
                <span class="yen-symbol">¥</span>
                <input type="number" name="price" class="price-input">
            </div>
        </div>
        @error('price')
            <p class="error">{{ $message }}</p>
        @enderror

        <button type="submit" class="submit-button">出品する</button>
    </form>
</div>

<script>
    document.getElementById('image-input').addEventListener('change', function(event) {
        const preview = document.getElementById('preview');
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
        }
    });
</script>
@endsection