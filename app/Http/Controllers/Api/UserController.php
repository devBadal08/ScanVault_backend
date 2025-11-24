<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\UserDeleted;

class UserController extends Controller
{
    // Check if user exists
    public function checkUser(Request $request)
    {
        $userId = $request->user()->id ?? null;

        if ($userId && User::find($userId)) {
            return response()->json([
                'exists' => true,
                'message' => 'User still exists'
            ], 200);
        } else {
            return response()->json([
                'exists' => false,
                'message' => 'User deleted or not found'
            ], 401); // Unauthorized
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        if ($user) {
            event(new UserDeleted($user)); // <-- pass $user
            $user->delete();
        }

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }

    public function saveFcmToken(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'device_token' => 'required'
        ]);

        $user = User::find($request->user_id);
        $user->device_token = $request->device_token;
        $user->save();

        return response()->json(['success' => true, 'message' => 'Token saved']);
    }
}
