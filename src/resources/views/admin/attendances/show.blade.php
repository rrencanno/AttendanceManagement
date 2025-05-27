@extends('admin.layouts.app')

@section('title', $attendance->user->name . 'さんの勤怠詳細 (' . \Carbon\Carbon::parse($attendance->work_date)->isoFormat('YYYY/M/D') . ') - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendances/show.css') }}">
@endsection

@section('content')
<div class="admin-attendance-detail-container">
    <h2 class="page-title">勤怠詳細</h2>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.attendances.update', $attendance->id) }}">
        @csrf
        @method('PUT') {{-- 更新なのでPUTメソッドを指定 --}}

        <div class="detail-card">
            <div class="detail-item">
                <span class="item-label">名前</span>
                <span class="item-value">{{ $attendance->user->name }}</span>
            </div>

            <div class="detail-item">
                <span class="item-label">日付</span>
                <span class="item-value">
                    {{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('YYYY年 M月D日') }}
                </span>
            </div>

            <div class="detail-item">
                <span class="item-label">出勤・退勤</span>
                <div class="item-value time-inputs">
                    <input type="time" name="clock_in_time"
                           value="{{ old('clock_in_time', $attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '') }}"
                           required>
                    <span class="time-separator">〜</span>
                    <input type="time" name="clock_out_time"
                           value="{{ old('clock_out_time', $attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '') }}"
                           required>
                </div>
            </div>

            <div class="detail-item breaks-section">
                <span class="item-label">休憩</span>
                <div class="item-value" id="breakTimesContainerAdmin">
                    @php $breakIndex = 0; @endphp
                    @foreach ($breaks as $break) {{-- コントローラーから渡された$breaksを使用 --}}
                        <div class="break-time-group" data-index="{{ $breakIndex }}">
                            <input type="time" name="break_start_time[]"
                                   value="{{ old('break_start_time.'.$breakIndex, $break->break_start_time ? \Carbon\Carbon::parse($break->break_start_time)->format('H:i') : '') }}">
                            <span class="time-separator">〜</span>
                            <input type="time" name="break_end_time[]"
                                   value="{{ old('break_end_time.'.$breakIndex, $break->break_end_time ? \Carbon\Carbon::parse($break->break_end_time)->format('H:i') : '') }}">
                            @if ($breakIndex > 0 || ($breakIndex === 0 && count($breaks) > 1) ) {{-- 最初の行でも複数あれば削除可能に --}}
                                <button type="button" class="btn-remove-break" onclick="removeBreakAdmin(this)">削除</button>
                            @endif
                        </div>
                        @php $breakIndex++; @endphp
                    @endforeach
                </div>
                <button type="button" id="addBreakButtonAdmin" class="btn-add-break">休憩追加</button>
            </div>

            <div class="detail-item remarks-item">
                <span class="item-label">備考</span>
                <div class="item-value">
                    <textarea name="remarks" rows="3">{{ old('remarks', $attendance->remarks) }}</textarea>
                </div>
            </div>
        </div>

        <div class="action-button-container">
            <button type="submit" class="btn btn-submit-update">修正</button>
        </div>
    </form>
    <div class="back-link-container">
        {{-- 戻るボタンのリンク先は、日付指定の勤怠一覧画面に戻るのが親切 --}}
        <a href="{{ route('admin.attendances.list', ['date' => $attendance->work_date]) }}" class="btn btn-back">戻る</a>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBreakButtonAdmin = document.getElementById('addBreakButtonAdmin');
    const breakTimesContainerAdmin = document.getElementById('breakTimesContainerAdmin');
    // 初期表示の休憩グループ数を取得 (削除ボタンの表示制御のため)
    let initialBreakGroups = breakTimesContainerAdmin.querySelectorAll('.break-time-group').length;

    if (addBreakButtonAdmin) {
        addBreakButtonAdmin.addEventListener('click', function() {
            const breakGroups = breakTimesContainerAdmin.querySelectorAll('.break-time-group');
            let newIndex = breakGroups.length > 0 ? parseInt(breakGroups[breakGroups.length - 1].dataset.index) + 1 : 0;

            const newBreakGroup = document.createElement('div');
            newBreakGroup.classList.add('break-time-group');
            newBreakGroup.dataset.index = newIndex;
            newBreakGroup.innerHTML = `
                <input type="time" name="break_start_time[]">
                <span class="time-separator">〜</span>
                <input type="time" name="break_end_time[]">
                <button type="button" class="btn-remove-break" onclick="removeBreakAdmin(this)">削除</button>
            `;
            breakTimesContainerAdmin.appendChild(newBreakGroup);
            updateRemoveButtonsVisibilityAdmin();
        });
    }
    updateRemoveButtonsVisibilityAdmin(); // 初期ロード時にも実行
});

function removeBreakAdmin(button) {
    const breakGroup = button.closest('.break-time-group');
    if (breakGroup) {
        breakGroup.remove();
        updateRemoveButtonsVisibilityAdmin();
    }
}

function updateRemoveButtonsVisibilityAdmin() {
    const container = document.getElementById('breakTimesContainerAdmin');
    const breakGroups = container.querySelectorAll('.break-time-group');
    breakGroups.forEach((group, index) => {
        let removeButton = group.querySelector('.btn-remove-break');
        if (breakGroups.length > 1) { // 2つ以上あれば常に表示
            if (!removeButton) { // もし削除ボタンがなければ追加（最初の要素にはない可能性があるため）
                const newRemoveButton = document.createElement('button');
                newRemoveButton.type = 'button';
                newRemoveButton.classList.add('btn-remove-break');
                newRemoveButton.textContent = '削除';
                newRemoveButton.onclick = function() { removeBreakAdmin(this); };
                group.appendChild(newRemoveButton);
            }
            removeButton.style.display = '';
        } else if (removeButton) { // 1つしかない場合は削除ボタンを隠す
            removeButton.style.display = 'none';
        }
    });
}
</script>
@endpush