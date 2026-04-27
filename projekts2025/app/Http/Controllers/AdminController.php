<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $orderBy = (string) $request->query('order_by', 'username_asc');
        $q = trim((string) $request->query('q', ''));

        $query = User::query();

        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $query->where(function ($w) use ($like) {
                $w->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        switch ($orderBy) {
            case 'email_asc':
                $query->orderBy('email', 'asc');
                break;
            case 'email_desc':
                $query->orderBy('email', 'desc');
                break;
            case 'username_asc':
                $query->orderBy('username', 'asc');
                break;
            case 'username_desc':
                $query->orderBy('username', 'desc');
                break;
            default:
                $query->orderBy('username', 'asc');
                break;
        }

        $users = $query->get();

        $adminUsernames = array_values(array_filter(array_map(
            fn ($u) => strtolower((string) $u),
            config('admin.usernames', [])
        )));

        $stats = [
            'users' => User::query()->count(),
            'admins' => count($adminUsernames) > 0
                ? User::query()->whereRaw(
                    'LOWER(username) in ('.implode(',', array_fill(0, count($adminUsernames), '?')).')',
                    $adminUsernames
                )->count()
                : 0,
        ];

        return view('admin.index', compact('users', 'stats', 'q', 'orderBy'));
    }

    public function deleteUser(Request $request, int $id)
    {
        $actor = Auth::user();
        $user = User::query()->find($id);

        if (! $user) {
            return redirect()
                ->route('admin')
                ->with('error', 'Lietotājs nav atrasts.');
        }

        if ($actor && (int) $actor->id === (int) $user->id) {
            return redirect()
                ->route('admin')
                ->with('error', 'Nevar dzēst pašu savu kontu.');
        }

        if ($user->isAdmin()) {
            return redirect()
                ->route('admin')
                ->with('error', 'Nevar dzēst administratora kontu.');
        }

        $user->delete();

        return redirect()
            ->route('admin')
            ->with('success', 'Lietotājs tika dzēsts.');
    }
}
