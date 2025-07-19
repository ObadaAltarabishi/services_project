<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(request $request)
    {
        $user = User::where('id', $request->id)->with('Profile', 'Services')->first();
        return response()->json($user, 200);
    }
    public function show(Profile $profile)
    {
        if (!Gate::allows('view-profile', $profile)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $profile->load(['user.services' => function ($query) {
            // Only show accepted services to non-admin viewers
            if (!auth()->user()->isAdmin()) {
                $query->where('status', 'accepted');
            }

            // Eager load additional relationships
            $query->with(['category', 'images']);
        }]);
    }

    public function update(Request $request, Profile $profile)
    {
        if (!Gate::allows('update-profile', $profile)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $request->validate([
            'description' => 'sometimes|string',
            'picture_url' => 'sometimes|url',
            'experience_years' => 'sometimes|integer|min:0',
            'age' => 'sometimes|integer|min:0',
            'location' => 'sometimes|string',
        ]);
        if (is_null($request->picture_url ?? null)) {
            unset($request->picture_url);
        } else {
            $name = $request->picture_url->getClientOriginalName();
            $newName = rand(9999999999, 99999999999) . $name;
            $request->picture_url->move(public_path('images'), $newName);
            $request->merge([
                'picture_url' => URL::to('images/' . $newName)
            ]);
        }
        $profile->update($request->all());

        return $profile;
    }

    /*public function update(Request $request, Profile $profile)
{
    // Verify the authenticated user owns this profile
    if ($request->user()->id !== $profile->user_id) {
        return response()->json([
            'message' => 'Unauthorized - You can only update your own profile',
            'success' => false
        ], 403);
    }

    $validatedData = $request->validate([
        'description' => 'sometimes|string|max:1000',
        'picture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
        'experience_years' => [
            'sometimes',
            'integer',
            'min:0',
            'max:70',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->age && ($request->age - $value < 15)) {
                    $fail('Your age must be at least 15 years more than your experience.');
                }
            }
        ],
        'age' => [
            'sometimes',
            'integer',
            'min:18',
            'max:100',
            function ($attribute, $value, $fail) use ($request) {
                if ($request->experience_years && ($value - $request->experience_years < 15)) {
                    $fail('Your age must be at least 15 years more than your experience.');
                }
            }
        ],
        'location' => 'sometimes|string|max:255'
    ]);

    try {
        // Handle picture upload if present
        if ($request->hasFile('picture')) {
            // Delete old picture if exists
            if ($profile->picture_url) {
                Storage::delete($profile->picture_url);
            }

            $path = $request->file('picture')->store('profile_images', 'public');
            $validatedData['picture_url'] = Storage::url($path);
        }

        // Update profile with validated data
        $profile->update($validatedData);

        return response()->json([
            'message' => 'Profile updated successfully',
            'success' => true,
            'profile' => $profile->fresh()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Profile update failed',
            'error' => $e->getMessage(),
            'success' => false
        ], 500);
    }
}*/
}
