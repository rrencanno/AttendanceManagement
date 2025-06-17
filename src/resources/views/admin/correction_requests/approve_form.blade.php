@extends('admin.layouts.app')

@section('title', '申請ID:'.$correction_request->id.' の承認 - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/correction_requests/approve_form.css') }}">
@endsection

@section('content')
<div class="admin-approve-form-container">
    <h2 class="page-title">勤怠詳細</h2>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="approval-details">
        <div class="detail-row">
            <span class="item-label">名前</span>
            <span class="item-value">{{ $correction_request->user ? $correction_request->user->name : 'N/A' }}</span>
        </div>

        <div class="detail-row">
            <span class="item-label">日付</span>
            <span class="item-value">
                {{ \Carbon\Carbon::parse($correction_request->attendance->work_date)->isoFormat('YYYY年') }}
                     
                {{ \Carbon\Carbon::parse($correction_request->attendance->work_date)->isoFormat('M月D日') }}
            </span>
        </div>

        <div class="detail-row">
            <span class="item-label">出勤・退勤</span>
            <span class="item-value time-display">
                {{ $correction_request->requested_clock_in_time ? \Carbon\Carbon::parse($correction_request->requested_clock_in_time)->format('H:i') : '-' }}
                <span class="time-separator">〜</span>
                {{ $correction_request->requested_clock_out_time ? \Carbon\Carbon::parse($correction_request->requested_clock_out_time)->format('H:i') : '-' }}
            </span>
        </div>

        @php $breakDisplayedCount = 0; @endphp
        @foreach ($requestedBreaks as $index => $break)
            @if ($break->start || $break->end)
                <div class="detail-row">
                    <span class="item-label">休憩{{ $breakDisplayedCount > 0 ? ' ' . ($breakDisplayedCount + 1) : '' }}</span>
                    <span class="item-value time-display">
                        {{ $break->start ? \Carbon\Carbon::createFromTimeString($break->start)->format('H:i') : '-' }}
                        <span class="time-separator">〜</span>
                        {{ $break->end ? \Carbon\Carbon::createFromTimeString($break->end)->format('H:i') : '-' }}
                    </span>
                </div>
                @php $breakDisplayedCount++; @endphp
            @endif
        @endforeach
        
        {{-- 空の休憩欄の表示ロジック --}}
        @if ($breakDisplayedCount < 2 && empty($correction_request->requested_break_details) && count($requestedBreaks) === 1 && !$requestedBreaks[0]->start && !$requestedBreaks[0]->end)
            @if($breakDisplayedCount === 0)
            <div class="detail-row">
                <span class="item-label">休憩</span>
                <span class="item-value time-display">
                    <span class="empty-time-box"></span>
                    <span class="time-separator">〜</span>
                    <span class="empty-time-box"></span>
                </span>
            </div>
            @endif
            <div class="detail-row">
                <span class="item-label">休憩 2</span>
                <span class="item-value time-display">
                    <span class="empty-time-box"></span>
                    <span class="time-separator">〜</span>
                    <span class="empty-time-box"></span>
                </span>
            </div>
        @elseif ($breakDisplayedCount === 1)
             <div class="detail-row">
                <span class="item-label">休憩 2</span>
                <span class="item-value time-display">
                    <span class="empty-time-box"></span>
                    <span class="time-separator">〜</span>
                    <span class="empty-time-box"></span>
                </span>
            </div>
        @endif


        <div class="detail-row remarks-row">
            <span class="item-label">備考</span>
            <span class="item-value remarks-display-box">
                {!! nl2br(e($correction_request->requested_note ?: '')) !!}
            </span>
        </div>
    </div>


    <form method="POST" action="{{ route('admin.correction_requests.process', $correction_request->id) }}" class="approval-form">
        @csrf
        <div class="form-actions">
            @if ($correction_request->status === 'pending')
                <button type="submit" name="action" value="approve" class="btn btn-approve">承認</button>
            @else
                <p class="processed-message">この申請は既に {{ $correction_request->status == 'approved' ? '承認済み' : '処理済み' }} です。</p>
            @endif
        </div>
    </form>

    <div class="back-link-container">
        <a href="{{ route('admin.correction_requests.index', ['status' => $correction_request->status]) }}" class="btn btn-back">申請一覧へ戻る</a>
    </div>
</div>
@endsection