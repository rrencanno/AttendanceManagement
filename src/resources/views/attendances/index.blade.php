@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendances/index.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="attendance-status-badge">
        @if($status == 'unstarted' || $status == 'finished_today')
            勤務外
        @elseif($status == 'working')
            勤務中
        @elseif($status == 'on_break')
            休憩中
        @endif
    </div>

    <div class="date-display">
        {{ \Carbon\Carbon::parse($today)->isoFormat('YYYY年M月D日(ddd)') }}
    </div>
    <div class="time-display" id="currentTime">
        {{-- JavaScriptで更新 --}}
    </div>

    <div class="action-buttons">
        @if($status == 'unstarted')
            <form method="POST" action="{{ route('attendances.clockin') }}">
                @csrf
                <button type="submit" class="btn btn-clock-in">出勤</button>
            </form>
        @elseif($status == 'working')
            {{-- 退勤ボタン --}}
            <form method="POST" action="{{ route('attendances.clockout') }}"> {{-- 後でルート作成 --}}
                @csrf
                <button type="submit" class="btn btn-clock-out">退勤</button>
            </form>
            {{-- 休憩開始ボタン --}}
            <form method="POST" action="{{ route('attendances.break.start') }}"> {{-- 後でルート作成 --}}
                @csrf
                <button type="submit" class="btn btn-break-start">休憩入</button>
            </form>
        @elseif($status == 'on_break')
            {{-- 休憩終了ボタン --}}
            <form method="POST" action="{{ route('attendances.break.end') }}"> {{-- 後でルート作成 --}}
                @csrf
                <button type="submit" class="btn btn-break-end">休憩戻</button>
            </form>
        @elseif($status == 'finished_today') {{-- 今日の勤務終了後 --}}
            <p class="work-finished-message">お疲れ様でした。</p>
        @endif
    </div>

    @if (session('status'))
        <div class="alert alert-success mt-3">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger mt-3">
            {{ session('error') }}
        </div>
    @endif
</div>

<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        // const seconds = String(now.getSeconds()).padStart(2, '0'); // 秒も表示する場合
        document.getElementById('currentTime').textContent = `${hours}:${minutes}`;
    }
    setInterval(updateTime, 1000); // 1秒ごとに更新
    updateTime(); // 初期表示
</script>
@endsection