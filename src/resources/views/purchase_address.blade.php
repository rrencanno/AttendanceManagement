@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase_address.css') }}">
@endsection

@section('content')
<div class="address-form">
    <h2>住所の変更</h2>

    <form method="POST" action="{{ route('purchase.address.update', ['item_id' => $item_id]) }}">
        @csrf
        @method('PATCH')
        <label for="post_code">郵便番号</label>
        <input type="text" name="post_code" id="post_code" value="{{ old('post_code', $address->post_code ?? '') }}">

        <label for="address">住所</label>
        <input type="text" name="address" id="address" value="{{ old('address', $address->address ?? '') }}">

        <label for="building_name">建物名</label>
        <input type="text" name="building_name" id="building_name" value="{{ old('building_name', $address->building_name ?? '') }}">

        <button type="submit">更新する</button>
    </form>
</div>
@endsection