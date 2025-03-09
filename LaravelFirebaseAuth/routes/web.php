<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Authentication routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Save user data after Firebase registration
Route::post('/save-user', [UserController::class, 'saveUser']);

// Handle Firebase login
Route::post('/firebase-login', [UserController::class, 'firebaseLogin'])
    ->name('firebase.login');

// Handle Firebase logout
Route::post('/firebase/logout', [UserController::class, 'logout'])
    ->name('firebase.logout');

Route::get('/email-verification', function () {
    return view('auth.verify-email'); // Ensure this view exists
})->name('email-verification');

Route::post('/resend-verification-email', [UserController::class, 'resendVerificationEmail']);

// OTP-related routes
Route::post('/send-otp', [UserController::class, 'sendOTP']);
Route::post('/verify-otp', [UserController::class, 'verifyOTP'])->name('verify.otp');
Route::post('/resend-otp', [UserController::class, 'resendOTP']); // Moved outside the closure

Route::get('/otp-verification', function () {
    return view('otp-verification');
});

// Include default Laravel authentication routes
require __DIR__.'/auth.php';