<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Events\UserDeleted;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

    public function uploadSelfie(Request $request)
    {
        $request->validate([
            'selfie' => 'required|image|max:5120', // 5MB
        ]);

        $user = auth()->user();

        $path = $request->file('selfie')
            ->store('profile-photos', 'public');

        // delete old photo (optional)
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        $user->update([
            'profile_photo' => $path
        ]);

        return response()->json([
            'success' => true,
            'photo' => asset('storage/' . $path)
        ]);
    }

    public function removeProfilePhoto(Request $request)
    {
        $user = auth()->user();

        if ($user->profile_photo) {
            $path = str_replace('/storage/', '', $user->profile_photo);

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }

        $user->profile_photo = null;
        $user->save();

        return response()->json([
            'message' => 'Profile photo removed',
        ]);
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
