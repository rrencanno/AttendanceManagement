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
            @forelse ($attendances as $attendance)
            <tr>
                <td>{{ \Carbon\Carbon::parse($attendance->work_date)->isoFormat('MM/DD(ddd)') }}</td>
                <td>{{ $attendance->clock_in_time ? \Carbon\Carbon::parse($attendance->clock_in_time)->format('H:i') : '-' }}</td>
                <td>{{ $attendance->clock_out_time ? \Carbon\Carbon::parse($attendance->clock_out_time)->format('H:i') : '-' }}</td>
                <td>{{ $attendance->formatted_total_break_time ?? '00:00' }}</td>
                <td>{{ $attendance->formatted_total_work_time ?? '00:00' }}</td>
                <td>
                    <a href="{{ route('admin.attendances.show', $attendance->id) }}" class="btn-detail">詳細</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="no-records">この月の勤怠記録はありません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($attendances->hasPages())
        <div class="pagination-container">
            {{ $attendances->appends(['month' => $targetMonth->format('Y-m')])->links() }}
        </div>
    @endif

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