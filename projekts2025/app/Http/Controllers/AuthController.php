<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showRegistrationForm()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
        ], [
            'username.required' => 'Lietotājvārds ir obligāts.',
            'username.unique' => 'Šis lietotājvārds jau ir aizņemts.',
            'email.required' => 'E-pasts ir obligāts.',
            'email.unique' => 'Šis e-pasts jau ir reģistrēts.',
            'password.required' => 'Parole ir obligāta.',
            'password.confirmed' => 'Paroles nesakrīt.',
            'password.min' => 'Parolei jābūt vismaz 6 simboliem.'
        ]);

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->intended(route('home'))->with('success', 'Reģistrācija veiksmīga!');
    }

    public function showLoginForm()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Lietotājvārds ir obligāts.',
            'password.required' => 'Parole ir obligāta.'
        ]);

        if (Auth::attempt($request->only('username', 'password'))) {
            return redirect()->intended(route('home'))->with('success', 'Pieteikšanās veiksmīga!');
        }

        return back()->withErrors([
            'username' => 'Nepareizs lietotājvārds vai parole.'
        ])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Jūs esat veiksmīgi izrakstījies!');
    }
}
