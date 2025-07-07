<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role === 'Super Admin' || $user->role === 'admin' || $user->role === 'manager') {
                return redirect('/admin');
            } else {
                Auth::logout();
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account does not have a valid role.',
                ]);
            }
        }

        return back()->withErrors([
            'email' => 'Invalid email or password',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
