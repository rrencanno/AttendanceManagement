@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile-container">
    <h2>プロフィール設定</h2>

    <form method="POST" action="{{ route('mypage.profile.update') }}" enctype="multipart/form-data">
        @csrf
        <div class="profile-image-area">
            @if ($user->image_url)
                <img id="preview" src="{{ asset('storage/' . $user->image_url) }}" alt="プロフィール画像" class="profile-image">
            @else
                <div id="preview" class="profile-placeholder"></div>
            @endif

            <label class="image-select-button">
                画像を選択する
                <input type="file" name="image_url" id="profile_image_input" hidden>
            </label>
            @error('image_url')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        @foreach ([
            'name' => 'ユーザー名',
            'post_code' => '郵便番号',
            'address' => '住所',
            'building_name' => '建物名'
        ] as $field => $label)
            <div class="form-section">
                <label for="{{ $field }}">{{ $label }}</label>
                <input type="text" name="{{ $field }}" id="{{ $field }}" value="{{ old($field, $user->$field) }}">
                @error($field)
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="submit-button">更新する</button>
    </form>
</div>
@endsection

@section('js')
<script>
    document.getElementById('profile_image_input').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('preview');

        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(event) {
                if (preview.tagName.toLowerCase() === 'div') {
                    const newImg = document.createElement('img');
                    newImg.src = event.target.result;
                    newImg.className = 'profile-image';
                    newImg.id = 'preview';
                    preview.replaceWith(newImg);
                } else {
                    preview.src = event.target.result;
                }
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endsection