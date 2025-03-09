<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FirebaseLoginController extends Controller
{
    public function firebaseLogin(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string', // Firebase ID token
            'email' => 'required|email',
            'name' => 'required|string',
        ]);
    
        $idToken = $request->input('id_token');
    
        try {
            // Initialize Firebase
            $factory = (new Factory)->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));
            $auth = $factory->createAuth();
    
            // Verify the Firebase ID token
            $verifiedIdToken = $auth->verifyIdToken($idToken);
    
            // Get the Firebase UID
            $firebaseUid = $verifiedIdToken->claims()->get('sub');
    
            // Find or create the user in your Laravel database
            $user = User::firstOrCreate(
                ['firebase_uid' => $firebaseUid],
                [
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => null, // No password needed
                ]
            );
    
            // Log in the user
            LaravelAuth::login($user);
    
            // Return a success response
            return response()->json([
                'message' => 'User authenticated successfully',
                'user' => $user,
            ]);
        } catch (FailedToVerifyToken $e) {
            return response()->json(['error' => 'Invalid ID token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Internal Server Error: ' . $e->getMessage()], 500);
        }
    }
}