<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth as Auth;

class UserLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        $user = Auth::user();

        // Optional: Check user role (if needed)
        if (!$user->hasRole('user')) {
            return response()->json(['message' => 'Unauthorized role'], 403);
        }

        // Generate token using Laravel Sanctum
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user'  => $user->load('companies'),
        ]);
    }
}
