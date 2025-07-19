<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // User fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'phone_number' => 'required|numeric|digits_between:9,11|unique:users,phone_number',
            'role' => 'required',
            // Profile fields
            'description' => 'required|string|max:1000',
            'picture' => 'nullable|max:5120', // 5MB max
            'experience_years' => [
                'required',
                'integer',
                'min:0',
                'max:70',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->age - $value <= 14) {
                        $fail('The age must be at least 15 years greater than years of experience.');
                    }
                }
            ],
            'age' => [
                'required',
                'integer',
                'min:18',
                'max:100',
                function ($attribute, $value, $fail) use ($request) {
                    if ($value - $request->experience_years <= 14) {
                        $fail('The age must be at least 18 years greater than years of experience.');
                    }
                }
            ],
            'location' => 'required|string|max:255'
        ]);

        // Rest of the method remains the same...
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $validatedData = $validator->validated();

            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone_number' => $validatedData['phone_number'],
                'verification_code' => Str::random(6),
                'verification_code_sent_at' => now(),
                'role' => $validatedData['role']
            ]);

            Mail::to($user->email)->send(new VerificationCodeMail($user->verification_code));

            // Handle profile picture upload
            $picturePath = null;
            // if ($request->hasFile('picture')) {
            $name = $request->picture->getClientOriginalName();
            $newName = rand(9999999999, 99999999999) . $name;
            $request->picture->move(public_path('images'), $newName);

            $picturePath = URl::to('images', $newName);
            // $picturePath = $request->file('picture')->store('profiles', 'public');
            // }

            // Create profile
            $user->profile()->create([
                'description' => $validatedData['description'],
                'picture_url' => $picturePath ? $picturePath : null,
                'experience_years' => $validatedData['experience_years'],
                'age' => $validatedData['age'],
                'location' => $validatedData['location']
            ]);

            // Create wallet
            $user->wallet()->create(['balance' => 0]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'message' => 'User registered successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load(['wallet', 'profile'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // First find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (!$user) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        // Check if account is restricted
        if ($user->report_count >= 2) {
            return response()->json([
                'message' => 'Your account has been restricted due to multiple reports. Please contact support.'
            ], 403);
        }

        // Verify credentials
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details'
            ], 401);
        }

        // Regenerate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(['wallet', 'profile']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load(['wallet', 'profile']));
    }
}
