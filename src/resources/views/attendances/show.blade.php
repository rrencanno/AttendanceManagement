@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/show.css') }}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <h2 class="page-title">勤怠詳細</h2>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
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

    @php
        $isPendingCorrection = $attendance->latestPendingCorrectionRequest && $attendance->latestPendingCorrectionRequest->status == 'pending';
        $requestedClockIn = $isPendingCorrection && $attendance->latestPendingCorrectionRequest->requested_clock_in_time
                            ? \Carbon\Carbon::parse($attendance->latestPendingCorrectionRequest->requested_clock_in_time)->format('H:i')
                            : ($attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '');
        $requestedClockOut = $isPendingCorrection && $attendance->latestPendingCorrectionRequest->requested_clock_out_time
                            ? \Carbon\Carbon::parse($attendance->latestPendingCorrectionRequest->requested_clock_out_time)->format('H:i')
                            : ($attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '');
        $requestedNoteContent = $isPendingCorrection
                                ? $attendance->latestPendingCorrectionRequest->requested_note
                                : $attendance->note;

    @endphp

    <form method="POST" action="{{ route('attendances.request_correction', $attendance->id) }}">
        @csrf
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
                           value="{{ old('clock_in_time', $requestedClockIn) }}"
                           {{ $isPendingCorrection ? 'readonly' : '' }} required>
                    <span class="time-separator">〜</span>
                    <input type="time" name="clock_out_time"
                           value="{{ old('clock_out_time', $requestedClockOut) }}"
                           {{ $isPendingCorrection ? 'readonly' : '' }} required>
                </div>
            </div>

            <div class="detail-item breaks-section">
                <span class="item-label">休憩</span>
                <div class="item-value" id="breakTimesContainer">
                    @php $breakIndex = 0; @endphp
                    @forelse ($displayBreaks as $break)
                        <div class="break-time-group" data-index="{{ $breakIndex }}">
                            <input type="time" name="break_start_time[]"
                                   value="{{ old('break_start_time.'.$breakIndex, $break->start ? \Carbon\Carbon::createFromTimeString($break->start)->format('H:i') : '') }}"
                                   {{ $isPendingCorrection ? 'readonly' : '' }}>
                            <span class="time-separator">〜</span>
                            <input type="time" name="break_end_time[]"
                                   value="{{ old('break_end_time.'.$breakIndex, $break->end ? \Carbon\Carbon::createFromTimeString($break->end)->format('H:i') : '') }}"
                                   {{ $isPendingCorrection ? 'readonly' : '' }}>
                            @if (!$isPendingCorrection && $breakIndex > 0)
                                <button type="button" class="btn-remove-break" onclick="removeBreak(this)">削除</button>
                            @elseif (!$isPendingCorrection && count($displayBreaks) === 1 && $breakIndex === 0)
                                <!-- 最初の行は削除ボタンなし -->
                            @endif
                        </div>
                        @php $breakIndex++; @endphp
                    @empty
                        {{-- ここは通らないはずだが、念の為 --}}
                        <div class="break-time-group" data-index="0">
                             <input type="time" name="break_start_time[]" {{ $isPendingCorrection ? 'readonly' : '' }}>
                             <span class="time-separator">〜</span>
                             <input type="time" name="break_end_time[]" {{ $isPendingCorrection ? 'readonly' : '' }}>
                        </div>
                    @endforelse
                </div>
                @if (!$isPendingCorrection)
                    <button type="button" id="addBreakButton" class="btn-add-break">休憩追加</button>
                @endif
            </div>


            <div class="detail-item remarks-item">
                <span class="item-label">備考 (申請理由)</span>
                <div class="item-value">
                    <textarea name="requested_note" rows="3"
                              {{ $isPendingCorrection ? 'readonly' : '' }}>{{ old('requested_note', $requestedNoteContent) }}</textarea>
                </div>
            </div>
        </div>

        <div class="action-button-container">
            @if ($isPendingCorrection)
                <p class="correction-pending-message">承認待ちのため修正できません。</p>
            @else
                <button type="submit" class="btn btn-submit-correction">修正申請</button>
            @endif
        </div>
    </form>
    <div class="back-link-container">
        <a href="{{ $backUrl }}" class="btn btn-back">戻る</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addBreakButton = document.getElementById('addBreakButton');
    const breakTimesContainer = document.getElementById('breakTimesContainer');
    let breakIndexCounter = {{ count($displayBreaks) }};

    if (addBreakButton) {
        addBreakButton.addEventListener('click', function() {
            const newBreakGroup = document.createElement('div');
            newBreakGroup.classList.add('break-time-group');
            newBreakGroup.dataset.index = breakIndexCounter;
            newBreakGroup.innerHTML = `
                <input type="time" name="break_start_time[]">
                <span class="time-separator">〜</span>
                <input type="time" name="break_end_time[]">
                <button type="button" class="btn-remove-break" onclick="removeBreak(this)">削除</button>
            `;
            breakTimesContainer.appendChild(newBreakGroup);
            breakIndexCounter++;
        });
    }
});

function removeBreak(button) {
    const breakGroup = button.closest('.break-time-group');
    if (breakGroup) {
        breakGroup.remove();
    }
}
</script>
@endsection