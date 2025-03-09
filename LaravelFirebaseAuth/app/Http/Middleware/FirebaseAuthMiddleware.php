<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;
use Illuminate\Support\Facades\Http;

class FirebaseAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['error' => 'Authorization token required'], 401);
        }

        $token = str_replace('Bearer ', '', $token);

        try {
            // Fetch Firebase public keys
            $keys = Http::get('https://www.googleapis.com/service_accounts/v1/jwk/securetoken@system.gserviceaccount.com')->json();

            // Decode JWT
            $decoded = JWT::decode($token, JWK::parseKeySet($keys));

            // Get Firebase user ID
            $firebaseUid = $decoded->sub;

            // Store user in request
            $request->attributes->set('firebase_uid', $firebaseUid);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
        }
    }
}
