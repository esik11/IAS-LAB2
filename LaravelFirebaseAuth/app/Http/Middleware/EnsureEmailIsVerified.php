<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()->hasVerifiedEmail()) {
            return redirect('/email-verification'); // Redirect to email verification page
        }

        return $next($request);
    }
}
