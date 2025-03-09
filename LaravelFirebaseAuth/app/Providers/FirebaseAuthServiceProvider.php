<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class FirebaseAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('firebase', function ($app, array $config) {
            return new FirebaseUserProvider();
        });
    }

    public function register()
    {
        //
    }
}