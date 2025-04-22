<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Menampilkan formulir login.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('admin.login');
    }

    /**
     * Memproses login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $remember = $request->filled('remember');

        if (Auth::attempt($credentials, $remember)) {
            // Update last login timestamp
            $user = Auth::user();
            $user->last_login_at = now();
            $user->save();
            
            $request->session()->regenerate();
            
            Log::info('User logged in', ['username' => $credentials['username']]);
            
            return redirect()->intended(route('dashboard'));
        }

        Log::warning('Failed login attempt', ['username' => $credentials['username'], 'ip' => $request->ip()]);
        
        throw ValidationException::withMessages([
            'username' => [__('auth.failed')],
        ]);
    }

    /**
     * Memproses logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        Log::info('User logged out', ['username' => Auth::user()->username]);
        
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
}