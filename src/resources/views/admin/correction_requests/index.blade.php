@extends('admin.layouts.app')

@section('title', '申請一覧 - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction_requests/index.css') }}">
@endsection

@section('content')
<div class="admin-correction-request-list-container">
    <h2 class="page-title">申請一覧</h2>

    <div class="tabs-container">
        <a href="{{ route('admin.correction_requests.index', ['status' => 'pending']) }}"
           class="tab-button {{ $statusFilter == 'pending' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.correction_requests.index', ['status' => 'approved']) }}"
           class="tab-button {{ $statusFilter == 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="table-responsive-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
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
                    <td>{{ optional($request->user)->name ?? (optional($request->attendance)->user ? $request->attendance->user->name : 'N/A') }}</td>
                    <td>{{ $request->attendance ? \Carbon\Carbon::parse($request->attendance->work_date)->isoFormat('YYYY/MM/DD') : 'N/A' }}</td>
                    <td>{{ Str::limit($request->requested_note, 30, '...') }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->created_at)->isoFormat('YYYY/MM/DD HH:mm') }}</td>
                    <td>
                        <a href="{{ route('admin.correction_requests.show_approval_form', $request->id) }}" class="btn-detail">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="no-records">
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
    </div>

    @if ($correctionRequests->hasPages())
        <div class="pagination-container">
            {{ $correctionRequests->appends(['status' => $statusFilter])->links() }}
        </div>
    @endif

</div>
@endsection