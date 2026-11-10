<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['outlets' => Outlet::query()->where('is_active', true)->get()]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials + ['is_active' => true], $remember)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, atau akun tidak aktif.',
            ]);
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->outlet_id) {
            session(['outlet_id' => $user->outlet_id]);
        } else {
            session(['outlet_id' => Outlet::query()->where('is_active', true)->value('id')]);
        }

        return redirect()->intended(route('dashboard'))
            ->with('swal_success', 'Selamat datang, '.$user->name.'!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('swal_success', 'Anda berhasil keluar.');
    }
}
