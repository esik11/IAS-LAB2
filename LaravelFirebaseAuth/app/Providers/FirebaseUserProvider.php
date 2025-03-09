<?php

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Kreait\Firebase\Factory;
use App\Models\User;

class FirebaseUserProvider implements UserProvider
{
    private $firebaseAuth;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(storage_path('app/firebase-credentials.json'));
        $this->firebaseAuth = $factory->createAuth();
    }

    public function retrieveById($identifier)
    {
        return User::where('firebase_uid', $identifier)->first();
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Not used with Firebase
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (isset($credentials['email'])) {
            return User::where('email', $credentials['email'])->first();
        }
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        try {
            // Verify Firebase token
            $verifiedIdToken = $this->firebaseAuth->verifyIdToken($credentials['id_token']);
            $firebaseUid = $verifiedIdToken->claims()->get('sub');

            // Ensure the Firebase UID matches the user
            return $user->firebase_uid === $firebaseUid;
        } catch (\Exception $e) {
            return false;
        }
    }
}