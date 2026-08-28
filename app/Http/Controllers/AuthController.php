<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAr()) {
                return redirect()->route('ar.dashboard');
            }
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginInput = $request->input('login') ?? $request->input('email') ?? $request->input('username');

        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Password wajib diisi.',
        ]);

        if (empty($loginInput)) {
            throw ValidationException::withMessages([
                'login' => 'Username atau email wajib diisi.',
            ]);
        }

        $remember = $request->boolean('remember');
        $isEmail = (bool) filter_var($loginInput, FILTER_VALIDATE_EMAIL);

        $authenticated = false;

        if ($isEmail) {
            $authenticated = Auth::attempt(['email' => $loginInput, 'password' => $request->password], $remember);
        } else {
            // Coba via username terlebih dahulu, jika gagal coba via email
            $authenticated = Auth::attempt(['username' => $loginInput, 'password' => $request->password], $remember)
                || Auth::attempt(['email' => $loginInput, 'password' => $request->password], $remember);
        }

        if ($authenticated) {
            $request->session()->regenerate();

            $user = Auth::user();
            if ($user->isAr()) {
                return redirect()->intended(route('ar.dashboard'))->with('success', "Selamat datang kembali, {$user->name}!");
            }

            return redirect()->intended(route('dashboard'))->with('success', "Selamat datang kembali, {$user->name}!");
        }

        throw ValidationException::withMessages([
            'login' => 'Kombinasi username/email dan password yang Anda masukkan tidak sesuai.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
