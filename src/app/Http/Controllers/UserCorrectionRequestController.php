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
        $statusFilter = $request->query('status', 'pending');

        $query = AttendanceCorrectionRequest::where('user_id', $user->id)
                                           ->with('attendance')
                                           ->orderBy('created_at', 'desc');

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'approved') {
            $query->where('status', 'approved');
        }

        $correctionRequests = $query->paginate(10);

        return view('correction_requests.index', compact('correctionRequests', 'statusFilter'));
    }
}
