<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
//autentifikācija un konta izveide (reģistrācija, pieslēgšanās, izrakstīšanās, ievades validācija)
class AuthController extends Controller
{
    // Parāda reģistrācijas formu
    public function showRegistrationForm()
    {
        return view('register');
    }

    // Reģistrē jaunu lietotāju un pieslēdz viņu sistēmā
    public function register(Request $request)
    {
        // Pārbauda ievadītos datus (lietotājvārds, e-pasts, parole)
        $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[A-Za-z0-9]+$/', 'unique:users,username'],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
        ], [
            'username.required' => 'Lietotājvārds ir obligāts',
            'username.unique' => 'Šis lietotājvārds jau ir aizņemts',
            'username.regex' => 'Lietotājvārds drīkst saturēt tikai burtus un ciparus',
            'username.min' => 'Lietotājvārdam jābūt vismaz 3 simboliem',
            'username.max' => 'Lietotājvārds nedrīkst būt garāks par 30 simboliem',
            'email.required' => 'E-pasts ir obligāts',
            'email.unique' => 'Šis e-pasts jau ir reģistrēts',
            'password.required' => 'Parole ir obligāta',
            'password.confirmed' => 'Paroles nesakrīt',
            'password.min' => 'Parolei jābūt vismaz 6 simboliem',
        ]);

        // Izveido jaunu lietotāju datubāzē (paroli saglabā šifrētu)
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Automātiski ielogojas pēc reģistrācijas
        Auth::login($user);

        // Novirza uz paneli
        return redirect()->intended(route('home'))->with('success', 'Reģistrācija veiksmīga!');
    }

    // Parāda pieteikšanās formu
    public function showLoginForm()
    {
        return view('login');
    }

    // Pieslēdz lietotāju ar lietotājvārdu un paroli
    public function login(Request $request)
    {
        // Pārbauda, vai lauki ir aizpildīti
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], [
            'username.required' => 'Lietotājvārds ir obligāts',
            'password.required' => 'Parole ir obligāta',
        ]);

        // Mēģina autentificēt lietotāju
        if (Auth::attempt($request->only('username', 'password'))) {
            return redirect()->intended(route('home'))->with('success', 'Pieteikšanās veiksmīga!');
        }

        // Ja dati nav pareizi, atgriež kļūdu un saglabā ievadi
        return back()->withErrors([
            'username' => 'Nepareizs lietotājvārds vai parole',
        ])->withInput();
    }

    // Izraksta lietotāju un notīra sesiju
    public function logout(Request $request)
    {
        // Izlogo no sistēmas
        Auth::logout();

        // Padara sesiju nederīgu
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Novirza uz pieteikšanās lapu
        return redirect()->route('login')->with('success', 'Jūs esat veiksmīgi izrakstījies!');
    }
}
