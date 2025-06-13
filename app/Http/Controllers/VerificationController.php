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
        // Get authenticated user from token
        $user = $request->user();
        
        // Generate new code
        $code = Str::random(6);
        
        $user->update([
            'verification_code' => $code,
            'verification_code_sent_at' => now()
        ]);
        
        try {
            Mail::to($user->email)->send(new VerificationCodeMail($code));
            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email',
                'code' => $code // Only include this in development!
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
            'code' => 'required|string'
        ]);
        
        $user = $request->user();
            
        if ($user-> verification_code !== $request->code) {
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