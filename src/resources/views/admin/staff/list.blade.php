@extends('admin.layouts.app')

@section('title', 'スタッフ一覧 - 管理者画面')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin/staff/list.css') }}">
@endsection

@section('content')
<div class="admin-staff-list-container">
    <h2 class="page-title">スタッフ一覧</h2>

    <table class="staff-table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($staffMembers as $staff)
            <tr>
                <td>{{ $staff->name }}</td>
                <td>{{ $staff->email }}</td>
                <td>
                    {{-- スタッフ別勤怠一覧へのリンク。当月をデフォルトとする --}}
                    <a href="{{ route('admin.attendances.list_by_staff', ['user' => $staff->id, 'month' => now()->format('Y-m')]) }}" class="btn-detail">
                        詳細
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="no-records">登録されているスタッフはいません。</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if ($staffMembers->hasPages())
        <div class="pagination-container">
            {{ $staffMembers->links() }}
        </div>
    @endif
</div>
@endsection