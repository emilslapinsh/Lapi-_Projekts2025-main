<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $orderBy = $request->get('order_by', 'email_asc');

        switch ($orderBy) {
            case 'email_asc':
                $users = User::orderBy('email', 'asc')->get();
                break;
            case 'email_desc':
                $users = User::orderBy('email', 'desc')->get();
                break;
            case 'username_asc':
                $users = User::orderBy('username', 'asc')->get();
                break;
            case 'username_desc':
                $users = User::orderBy('username', 'desc')->get();
                break;
            default:
                $users = User::orderBy('email', 'asc')->get();
                break;
        }

        return view('admin.index', compact('users'));
    }

    public function deleteUser($id)
    {
        $user = User::find($id);

        if ($user) {
            $user->delete();
        }

        return redirect()->back();
    }

}
