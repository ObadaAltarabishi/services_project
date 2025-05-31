<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(Profile $profile)
    {
        if (!Gate::allows('view-profile', $profile)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

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