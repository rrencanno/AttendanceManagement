@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<form action="{{ route('purchase.store', ['item_id' => $product->id]) }}" method="POST">
    @csrf
    <div class="purchase-container">
        <div class="left-section">
            <div class="product-info-row">
                <img src="{{ asset('storage/' . $product->image_url) }}" alt="商品画像" class="product-image">
                <div class="product-details">
                    <h2>{{ $product->name }}</h2>
                    <p class="price">¥{{ number_format($product->price) }}</p>
                </div>
            </div>

            <div class="section">
                <label for="payment_method">支払い方法</label>
                <select name="payment_method" id="payment_method">
                    <option value="">選択してください</option>
                    <option value="コンビニ払い" {{ old('payment_method') == 'コンビニ払い' ? 'selected' : '' }}>コンビニ払い</option>
                    <option value="カード払い" {{ old('payment_method') == 'カード払い' ? 'selected' : '' }}>カード払い</option>
                </select>
            </div>
            @error('payment_method')
                <p class="error">{{ $message }}</p>
            @enderror

            <div class="section">
                <div class="section-address">
                    <div class="section-header">
                        <label>配送先</label>
                        <a href="{{ route('purchase.address.edit', ['item_id' => $product->id]) }}" class="edit-link">変更する</a>
                    </div>
                    <p>〒 {{ $address->post_code }}</p>
                    <p>{{ $address->address }} {{ $address->building_name }}</p>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="summary-section">
                <div class="summary">
                    <p>商品代金</p>
                    <p class="price">¥{{ number_format($product->price) }}</p>
                </div>
                <div class="summary">
                    <p>支払い方法</p>
                    <p id="summary-method">未選択</p>
                </div>
            </div>

            <button type="submit" class="purchase-button">購入する</button>
        </div>
    </div>
</form>

<script>
    const select = document.getElementById('payment_method');
    const summary = document.getElementById('summary-method');
    const hidden = document.getElementById('payment_method_hidden');

    select.addEventListener('change', function () {
        summary.textContent = this.value || '未選択';
        hidden.value = this.value;
    });
</script>
@endsection