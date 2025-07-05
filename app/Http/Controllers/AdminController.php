<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // Existing admin management methods
    public function index()
    {
        Gate::authorize('admin-action');
        return Admin::paginate(10);
    }

    public function store(Request $request)
    {
        Gate::authorize('admin-action');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:admins',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return $admin;
    }

    public function show(Admin $admin)
    {
        Gate::authorize('admin-action');
        return $admin;
    }

    public function update(Request $request, Admin $admin)
    {
        Gate::authorize('admin-action');
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:admins,email,'.$admin->id,
            'password' => ['sometimes', Rules\Password::defaults()],
        ]);

        $data = $request->all();
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return $admin;
    }

    public function destroy(Admin $admin)
    {
        Gate::authorize('admin-action');
        $admin->delete();
        return response()->json(['message' => 'Admin deleted successfully']);
    }

    // Report management methods
    public function increaseReportCount(Request $request, User $user)
    {
        Gate::authorize('admin-action');
        
        $request->validate([
            'amount' => 'sometimes|integer|min:1|max:10',
        ]);

        $amount = $request->input('amount', 1);
        $user->increment('report_count', $amount);

        return response()->json([
            'message' => 'Report count increased successfully',
            'user' => $user->fresh(),
            'new_count' => $user->report_count
        ]);
    }

    public function decreaseReportCount(Request $request, User $user)
    {
        Gate::authorize('admin-action');
        
        $request->validate([
            'amount' => 'sometimes|integer|min:1|max:10',
        ]);

        $amount = $request->input('amount', 1);
        $newCount = max(0, $user->report_count - $amount);
        $user->update(['report_count' => $newCount]);

        return response()->json([
            'message' => 'Report count decreased successfully',
            'user' => $user->fresh(),
            'new_count' => $user->report_count
        ]);
    }

    public function resetReportCount(User $user)
    {
        Gate::authorize('admin-action');
        $user->update(['report_count' => 0]);

        return response()->json([
            'message' => 'Report count reset successfully',
            'user' => $user->fresh(),
            'new_count' => 0
        ]);
    }
}