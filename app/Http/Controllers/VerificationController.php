<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerificationCodeMail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VerificationController extends Controller
{
    public function sendVerificationCode(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        
        $user = User::where('email', $request->email)->first();
        
        $code = Str::random(6); // or mt_rand(100000, 999999)
        
        $user->update([
            'verification_code' => $code,
            'verification_code_sent_at' => now()
        ]);
        
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code));
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string'
        ]);
        
        $user = User::where('email', $request->email)
            ->where('verification_code', $request->code)
            ->first();
            
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }
        
        if (Carbon::parse($user->verification_code_sent_at)->addMinutes(15)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code expired'
            ], 422);
        }
        
        $user->update([
            'email_verified_at' => now(),
            'verification_code' => null,
            'verification_code_sent_at' => null
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    }
    
    public function resendCode(Request $request)
    {
        return $this->sendVerificationCode($request);
    }
}