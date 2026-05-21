<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
//administratora panelis lietotāju pārvaldībai (saraksts, meklēšana/kārtošana, lietotāja dzēšana ar drošības pārbaudēm)
class AdminController extends Controller
{
    // Admin panelis: lietotāju saraksts ar meklēšanu un kārtošanu
    public function index(Request $request)
    {
        // Nolasa filtru parametrus
        $orderBy = (string) $request->query('order_by', 'username_asc');
        $q = trim((string) $request->query('q', ''));

        // Sagatavo request lietotāju iegūšanai
        $query = User::query();

        // Meklē pēc lietotājvārda vai e-pasta
        if ($q !== '') {
            // Sagatavo "LIKE" meklēšanu
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->where('username', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        // Kārtošanas loģika (pēc lietotājvārda vai e-pasta)
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

        // Iegūst lietotājus pēc filtriem
        $users = $query->get();

        // Nolasa administratoru lietotājvārdus
        $adminUsernames = array_values(array_filter(array_map(
            fn ($u) => strtolower((string) $u),
            config('admin.usernames', [])
        )));

        // Aprēķina lietotāju skaitu un administratoru skaitu
        $stats = [
            // Kopējais lietotāju skaits
            'users' => User::query()->count(),
            // Administratoru skaits pēc lietotājvārdu saraksta config failā
            'admins' => count($adminUsernames) > 0
                ? User::query()->whereRaw(
                    'LOWER(username) in ('.implode(',', array_fill(0, count($adminUsernames), '?')).')',
                    $adminUsernames
                )->count()
                : 0,
        ];

        return view('admin.index', compact('users', 'stats', 'q', 'orderBy'));
    }

    // Dzēš lietotāju (ar aizsardzību pret admina un paša dzēšanu)
    public function deleteUser(Request $request, int $id)
    {
        // Pašreizējais lietotājs un mērķa lietotājs
        $actor = Auth::user();
        $user = User::query()->find($id);

        // Ja lietotājs neeksistē, atgriež kļūdas paziņojumu
        if (! $user) {
            return redirect()
                ->route('admin')
                ->with('error', 'Lietotājs nav atrasts.');
        }

        // Neļauj dzēst savu kontu
        if ($actor && (int) $actor->id === (int) $user->id) {
            return redirect()
                ->route('admin')
                ->with('error', 'Nevar dzēst savu kontu.');
        }

        // Neļauj dzēst administratora kontu
        if ($user->isAdmin()) {
            return redirect()
                ->route('admin')
                ->with('error', 'Nevar dzēst administratora kontu.');
        }

        // Dzēš lietotāju
        $user->delete();

        return redirect()
            ->route('admin')
            ->with('success', 'Lietotājs tika dzēsts.');
    }
}
