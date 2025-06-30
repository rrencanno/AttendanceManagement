@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/correction_requests/index.css') }}">
@endsection

@section('content')
<div class="correction-request-list-container">
    <h2 class="page-title">申請一覧</h2>

    <div class="tabs-container">
        <a href="{{ route('correction_requests.index', ['status' => 'pending']) }}"
           class="tab-button {{ $statusFilter == 'pending' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('correction_requests.index', ['status' => 'approved']) }}"
           class="tab-button {{ $statusFilter == 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <table class="request-table">
        <thead>
            <tr>
                <th>状態</th>
                <th>対象日時</th>
                <th>申請理由 (備考)</th>
                <th>申請日時</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($correctionRequests as $request)
            <tr>
                <td>
                    @if ($request->status == 'pending')
                        <span class="status-badge status-pending">承認待ち</span>
                    @elseif ($request->status == 'approved')
                        <span class="status-badge status-approved">承認済み</span>
                    @endif
                </td>
                <td>{{ $request->attendance ? \Carbon\Carbon::parse($request->attendance->work_date)->isoFormat('YYYY/MM/DD') : 'N/A' }}</td>
                <td>{{ Str::limit($request->requested_note, 30, '...') }}</td>
                <td>{{ \Carbon\Carbon::parse($request->created_at)->isoFormat('YYYY/MM/DD HH:mm') }}</td>
                <td>
                    @if ($request->attendance)
                        <a href="{{ route('attendances.show', ['attendance' => $request->attendance_id, 'from' => 'requests']) }}" class="btn-detail">詳細</a>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="no-records">
                    @if ($statusFilter == 'pending')
                        承認待ちの申請はありません。
                    @else
                        承認済みの申請はありません。
                    @endif
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($correctionRequests->hasPages())
        <div class="pagination-container">
            {{ $correctionRequests->appends(['status' => $statusFilter])->links() }}
        </div>
    @endif

</div>
@endsection