<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(request $request)
    {
        $user = User::where('id', $request->id)->with('Profile', 'Services')->first();
        // $user = \Auth::user()->with(['profile']);
        return response()->json($user, 200);
    }
    public function show(Profile $profile)
    {
        if (!Gate::allows('view-profile', $profile)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        // $user = \Auth::user()->with(['profile']);
        return $profile->load('user');
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

        $profile->update($request->all());

        return $profile;
    }
}
