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
        // Step 1: Validate inputs
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        // Step 2: Attempt login
        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Step 3: Validate role
            $allowedRoles = ['Super Admin', 'admin', 'manager'];

            if (in_array($user->role, $allowedRoles)) {
                return redirect('/admin');
            } else {
                Auth::logout();
                return back()->withErrors([
                    'login_error' => 'Your account does not have permission to access this system.',
                ])->withInput($request->only('email'));
            }
        }

        // Step 4: Invalid email/password
        return back()->withErrors([
            'login_error' => 'Invalid email or password.',
        ])->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
