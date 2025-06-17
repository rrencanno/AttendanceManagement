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
        @if(count($dailyData) > 0)
                @foreach ($dailyData as $day)
                <tr>
                    <td>{{ $day['date']->isoFormat('MM/DD(ddd)') }}</td>
                    @if ($day['attendance']) {{-- その日の勤怠データがある場合 --}}
                        <td>{{ $day['attendance']->clock_in_time ? \Carbon\Carbon::parse($day['attendance']->clock_in_time)->format('H:i') : '' }}</td>

                        {{-- 退勤、休憩、合計の表示を clock_out_time の有無で分岐 --}}
                        @if ($day['attendance']->clock_out_time)
                            <td>{{ \Carbon\Carbon::parse($day['attendance']->clock_out_time)->format('H:i') }}</td>
                            <td>{{ $day['attendance']->formatted_total_break_time ?: '' }}</td>
                            <td>{{ $day['attendance']->formatted_total_work_time ?: '' }}</td>
                        @else
                            <td></td> {{-- 退勤: 空白 --}}
                            <td></td> {{-- 休憩: 空白 --}}
                            <td></td> {{-- 合計: 空白 --}}
                        @endif

                        <td>
                            @if ($day['attendance']->clock_in_time) {{-- 出勤記録がある場合のみ詳細ボタン表示 --}}
                                <a href="{{ route('attendances.show', ['attendance' => $day['attendance']->id, 'return_month' => $targetMonth->format('Y-m')]) }}" class="btn-detail">詳細</a>
                            @else
                                {{-- 詳細ボタンも表示しない場合は空 --}}
                            @endif
                        </td>
                    @else {{-- その日の勤怠データがない場合 --}}
                        <td></td> {{-- 出勤: 空白 --}}
                        <td></td> {{-- 退勤: 空白 --}}
                        <td></td> {{-- 休憩: 空白 --}}
                        <td></td> {{-- 合計: 空白 --}}
                        <td></td> {{-- 詳細: 空白 --}}
                    @endif
                </tr>
                @endforeach
            @else
                {{-- この分岐は基本的には通らないはず (dailyDataは必ずその月の日数分ある) --}}
                <tr>
                    <td colspan="6" class="no-records">表示できるデータがありません。</td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
@endsection