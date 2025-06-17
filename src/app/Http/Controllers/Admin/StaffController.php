<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $staffMembers = User::where('is_admin', false)
                            ->orderBy('id')
                            ->paginate(10);

        return view('admin.staff.list', compact('staffMembers'));
    }
}
