<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;

class UserCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $statusFilter = $request->query('status', 'pending'); // デフォルトは 'pending'

        $query = AttendanceCorrectionRequest::where('user_id', $user->id)
                                           ->with('attendance')
                                           ->orderBy('created_at', 'desc');

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'approved') { // 「承認済み」のみを対象
            $query->where('status', 'approved');
        }
        // 'all' などのパラメータで全てのステータスを表示するタブも追加可能

        $correctionRequests = $query->paginate(10);

        return view('correction_requests.index', compact('correctionRequests', 'statusFilter'));
    }
}
