@extends('admin.layouts.app')

@section('title', $user->name . 'さんの勤怠 (' . $targetMonth->format('Y年m月') . ') - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/attendances/list_by_staff.css') }}">
@endsection

@section('content')
<div class="admin-staff-attendance-container">
    <h2 class="page-title">{{ $user->name }}さんの勤怠</h2>

    <div class="month-navigation">
        <a href="{{ route('admin.attendances.list_by_staff', ['user' => $user->id, 'month' => $prevMonth]) }}" class="month-nav-button prev-month-btn">
            <i class="fa-solid fa-chevron-left"></i> 前月
        </a>
        <span class="current-month">{{ $targetMonth->format('Y / m') }}</span>
        <a href="{{ route('admin.attendances.list_by_staff', ['user' => $user->id, 'month' => $nextMonth]) }}" class="month-nav-button next-month-btn">
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
                        <td>{{ $day['attendance']->clock_out_time ? \Carbon\Carbon::parse($day['attendance']->clock_out_time)->format('H:i') : '' }}</td>
                        <td>{{ $day['attendance']->formatted_total_break_time && $day['attendance']->formatted_total_break_time !== '00:00' ? $day['attendance']->formatted_total_break_time : '' }}</td>
                        <td>{{ $day['attendance']->formatted_total_work_time && $day['attendance']->formatted_total_work_time !== '00:00' ? $day['attendance']->formatted_total_work_time : '' }}</td>
                        <td>
                            @if ($day['attendance']->clock_in_time)
                                <a href="{{ route('admin.attendances.show', $day['attendance']->id) }}" class="btn-detail">詳細</a>
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
                <tr>
                    <td colspan="6" class="no-records">表示できるデータがありません。</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="action-buttons-footer">
        <a href="{{ route('admin.attendances.export_csv_by_staff', ['user' => $user->id, 'month' => $targetMonth->format('Y-m')]) }}" class="btn btn-csv-export">
            CSV出力
        </a>
    </div>
    <div class="back-link-container">
        <a href="{{ route('admin.staff.list') }}" class="btn btn-back">スタッフ一覧へ戻る</a>
    </div>

</div>
@endsection