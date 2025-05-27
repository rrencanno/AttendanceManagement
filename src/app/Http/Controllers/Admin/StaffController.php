<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        // is_admin = false のユーザー（つまりスタッフ）のみを取得
        // もし管理者も含めて表示する場合は ->get() のみ
        $staffMembers = User::where('is_admin', false)
                            ->orderBy('id') // または名前順など
                            ->paginate(10); // 1ページあたり10件表示

        return view('admin.staff.list', compact('staffMembers'));
    }
}
