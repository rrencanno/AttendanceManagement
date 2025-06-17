@extends('admin.layouts.app')

@section('title', $targetDate->isoFormat('YYYY年M月D日').'の勤怠 - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendances/list.css') }}">
    {{-- カレンダーピッカー用のCSS (例: flatpickr) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endsection

@section('content')
<div class="admin-attendance-list-container">
    <h2 class="page-title">{{ $targetDate->isoFormat('YYYY年M月D日') }}の勤怠</h2>

    <div class="date-navigation">
        <a href="{{ route('admin.attendances.list', ['date' => $prevDate]) }}" class="date-nav-button prev-date-btn">
            <i class="fa-solid fa-chevron-left"></i> 前日
        </a>
        <input type="text" id="datePicker" value="{{ $targetDate->toDateString() }}" class="date-picker-input" readonly>
        <a href="{{ route('admin.attendances.list', ['date' => $nextDate]) }}" class="date-nav-button next-date-btn">
            翌日 <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user ? $attendance->user->name : 'N/A' }}</td>
                <td>{{ $attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '' }}</td>

                {{-- 退勤時刻の表示 --}}
                @if ($attendance->clock_out_time)
                    <td>{{ \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') }}</td>
                    <td>{{ $attendance->formatted_total_break_time ?? '' }}</td>
                    <td>{{ $attendance->formatted_total_work_time ?? '' }}</td>
                @else
                    <td></td> {{-- 退勤: 空白 --}}
                    <td></td> {{-- 休憩: 空白 --}}
                    <td></td> {{-- 合計: 空白 --}}
                @endif

                <td>
                    {{-- 詳細ボタンは出勤記録があれば表示 --}}
                    @if ($attendance->clock_in_time)
                        <a href="{{ route('admin.attendances.show', $attendance->id) }}" class="btn-detail">詳細</a>
                    @else
                        {{-- 出勤記録がなければ詳細ボタンも表示しない --}}
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="no-records">この日の勤怠記録はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($attendances->hasPages())
        <div class="pagination-container">
            {{ $attendances->appends(['date' => $targetDate->toDateString()])->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    {{-- カレンダーピッカー用のJS --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/ja.js"></script>
    <script>
        flatpickr("#datePicker", {
            dateFormat: "Y-m-d",
            defaultDate: "{{ $targetDate->toDateString() }}",
            locale: "ja",
            onChange: function(selectedDates, dateStr, instance) {
                if (dateStr) {
                    // 日付が変更されたら、その日付の勤怠一覧ページに遷移
                    window.location.href = `{{ route('admin.attendances.list') }}/${dateStr}`;
                }
            }
        });
    </script>
@endpush