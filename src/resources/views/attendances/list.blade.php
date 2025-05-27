@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/list.css') }}">
@endsection

@section('content')
<div class="attendance-list-container">
    <div class="month-navigation">
        <a href="{{ route('attendances.list', ['month' => $prevMonth]) }}" class="month-nav-button prev-month-btn">
            <i class="fa-solid fa-chevron-left"></i> 前月
        </a>
        <span class="current-month">{{ $targetMonth->format('Y/m') }}</span>
        <a href="{{ route('attendances.list', ['month' => $nextMonth]) }}" class="month-nav-button next-month-btn">
            翌月 <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @if($attendances->count() > 0)
                @foreach ($attendances as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)') }}</td>
                    <td>{{ $attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->formatted_total_break_time }}</td>
                    <td>{{ $attendance->formatted_total_work_time }}</td>
                    <td>
                        @if ($attendance->clock_in_time) {{-- 出勤記録がある場合のみ詳細ボタン表示 --}}
                            <a href="{{ route('attendances.show', $attendance->id) }}" class="btn-detail">詳細</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="6" class="no-records">この月の勤怠記録はありません。</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@endsection