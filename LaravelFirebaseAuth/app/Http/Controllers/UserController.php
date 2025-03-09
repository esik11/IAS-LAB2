<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;

class UserController extends Controller
{
    // Save user data after Firebase registration
    public function saveUser(Request $request)
    {
        Log::info("Save User Request:", $request->all());

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'firebase_uid' => 'required|string|max:255|unique:users',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'firebase_uid' => $request->firebase_uid,
            'password' => null,
        ]);

        Log::info("User saved in Laravel database:", ['user' => $user]);

        return response()->json([
            'message' => 'User saved successfully!',
            'user' => $user,
        ]);
    }

    // Handle Firebase login
    public function firebaseLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
            'email' => 'required|email',
        ]);

        $firebaseAuth = app('firebase.auth');
        try {
            $verifiedIdToken = $firebaseAuth->verifyIdToken($request->id_token);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            $firebaseUser = $firebaseAuth->getUser($firebaseUid);
            if (!$firebaseUser->emailVerified) {
                return response()->json(['error' => 'Email not verified. Please check your email.'], 401);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Invalid Firebase ID token'], 401);
        }

        $user = User::firstOrCreate(
            ['firebase_uid' => $firebaseUid],
            [
                'email' => $request->email,
                'name' => $request->name ?? 'User',
                'password' => null,
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, true);

        return response()->json([
            'message' => 'User authenticated successfully.',
            'user' => $user,
        ]);
    }

    // Generate and send OTP
    public function sendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            Log::error("User not found:", ['email' => $request->email]);
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        // Skip OTP if the user is already verified
        if ($user->email_verified_at) {
            return response()->json(['message' => 'User already verified. Redirecting to dashboard...']);
        }
    
        // Generate a 6-digit numeric OTP
        $otp = mt_rand(100000, 999999);
        Log::info("Generated OTP:", ['otp' => $otp]);
    
        // Store the OTP and its expiration time
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5); // OTP expires in 5 minutes
        $user->save();
    
        // Send the OTP via email
        try {
            Mail::to($user->email)->send(new SendOTP($otp));
            Log::info("OTP email sent to:", ['email' => $user->email]);
            return response()->json(['message' => 'OTP sent to your email.']);
        } catch (\Exception $e) {
            Log::error("Failed to send OTP email:", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to send OTP email.'], 500);
        }
    }

    // Verify OTP
    public function verifyOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|numeric',
        ]);
    
        // Find the user
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        // Check OTP validity
        if ($user->otp !== $request->otp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        }
    
        if ($user->otp_expires_at < now()) {
            return response()->json(['error' => 'OTP has expired.'], 400);
        }
    
        // Clear OTP after successful verification
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->email_verified_at = now(); // Mark email as verified
        $user->save();
    
        // Log in the user
        Auth::login($user, true);
    
        return response()->json(['message' => 'OTP verified successfully!']);
    }
    // Resend OTP
    public function resendOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
    
        // Find the user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            Log::error("User not found for OTP resend:", ['email' => $request->email]);
            return response()->json(['message' => 'User not found.'], 404);
        }
    
        // Generate a new 6-digit numeric OTP
        $otp = mt_rand(100000, 999999);
        Log::info("Generated new OTP:", ['otp' => $otp]);
    
        // Update the OTP and its expiration time
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(5); // OTP expires in 5 minutes
        $user->save();
    
        // Send the new OTP via email
        try {
            Mail::to($user->email)->send(new SendOTP($otp));
            Log::info("Resent OTP email to:", ['email' => $user->email]);
            return response()->json(['message' => 'New OTP sent to your email.']);
        } catch (\Exception $e) {
            Log::error("Failed to resend OTP email:", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to resend OTP. Please check your email settings.'], 500);
        }
    }
}