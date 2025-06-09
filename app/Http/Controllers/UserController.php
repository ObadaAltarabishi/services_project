<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        return User::with([ 'profile'])->paginate(10);
    }

    public function store(Request $request)
    {
        // التسجيل يتم عبر AuthController
        return response()->json(['message' => 'Use /register endpoint for registration'], 400);
    }

    public function show(User $user)
    {
        return $user->load([ 'profile', 'services']);
    }

    public function update(Request $request, User $user)
    {
        if (!Gate::allows('update-user', $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => ['sometimes', Rules\Password::defaults()],
        ]);

        $data = $request->all();
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return $user;
    }

    public function destroy(User $user)
    {
        if (!Gate::allows('delete-user', $user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}