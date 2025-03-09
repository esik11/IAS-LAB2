import './bootstrap';
import Alpine from 'alpinejs';
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
import { getAuth, createUserWithEmailAndPassword, signInWithEmailAndPassword, signOut, GoogleAuthProvider, signInWithPopup, sendEmailVerification } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-auth.js";

window.Alpine = Alpine;
Alpine.start();

console.log("App.js loaded!"); // Debugging

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyBYFyx7YUkm1Usc1CR2Xcu4A5EwBq-HlY8",
    authDomain: "laravel-auth-71bf8.firebaseapp.com",
    projectId: "laravel-auth-71bf8",
    storageBucket: "laravel-auth-71bf8.appspot.com",
    messagingSenderId: "583066212163",
    appId: "1:583066212163:web:3e3bd20688f3ed68e941f6",
    measurementId: "G-GD6H2TS8MW"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const provider = new GoogleAuthProvider();
const auth = getAuth(app);

console.log("Firebase initialized successfully!"); // Debugging
window.auth = auth; // Make auth globally available

// Google Sign-In
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded!"); // Debugging

    const googleLogin = document.getElementById("google-login-btn");
    if (googleLogin) {
        googleLogin.addEventListener("click", function (e) {
            e.preventDefault(); // Prevent form submission
            console.log("Google Sign-In button clicked!"); // Debugging

            signInWithPopup(auth, provider)
            .then((result) => {
                const credential = GoogleAuthProvider.credentialFromResult(result);
                const token = credential.accessToken;
                const user = result.user;
                console.log("Google Sign-In Success:", user);
        
                // Ensure the email is available
                if (!user.email) {
                    throw new Error("Email is required but not provided by Google.");
                }
        
                // Get the Firebase ID token
                return user.getIdToken();
            })
            .then((idToken) => {
                console.log("Firebase ID Token:", idToken);
        
                // Send the ID token and user data to Laravel backend
                return fetch('/firebase-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        id_token: idToken,
                        email: auth.currentUser.email, // Ensure email is included
                        name: auth.currentUser.displayName || 'User'
                    })
                });
            })
            .then((response) => response.json())
            .then((data) => {
                console.log("Login response:", data);
                window.location.href = "/dashboard"; // Redirect to dashboard
            })
            .catch((error) => {
                console.error("Google Sign-In Error:", error);
                alert("Error during Google Sign-In: " + error.message);
                });
        });
    } else {
        console.log("Google Sign-In button not found!");
    }

    // Check the current URL to determine which page we're on
    const currentUrl = window.location.pathname;

    if (currentUrl === '/register') {
        console.log("On registration page"); // Debugging
        setupRegistrationHandlers(auth);
    } else if (currentUrl === '/login') {
        console.log("On login page"); // Debugging
        setupLoginHandlers(auth);
    } else {
        console.log("On another page"); // Debugging
    }
});

// Function to set up registration handlers
const setupRegistrationHandlers = (auth) => {
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = document.getElementById('name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;

            createUserWithEmailAndPassword(auth, email, password)
                .then((userCredential) => {
                    console.log("User registered in Firebase:", userCredential.user);

                    // Send email verification
                    return sendEmailVerification(userCredential.user);
                })
                .then(() => {
                    console.log("Verification email sent!");
                    alert("Registration successful! Please check your email to verify your account.");

                    // Get the Firebase UID
                    const firebaseUid = auth.currentUser.uid;

                    // Send user data to Laravel backend
                    return fetch('/save-user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            name: name,
                            email: email,
                            firebase_uid: firebaseUid
                        })
                    });
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Failed to save user in Laravel database.");
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("User saved in Laravel database:", data);
                    return signOut(auth); // Sign out the user after registration
                })
                .then(() => {
                    console.log("User signed out after registration");
                    window.location.href = "/login"; // Redirect to login page
                })
                .catch((error) => {
                    console.error("Error during registration:", error.message);
                    alert("Error: " + error.message);
                });
        });
    }
};

// Function to set up login handlers
const setupLoginHandlers = (auth) => {
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;

            signInWithEmailAndPassword(auth, email, password)
                .then((userCredential) => {
                    const user = userCredential.user;

                    // Check if email is verified
                    if (!user.emailVerified) {
                        throw new Error("Please verify your account first.");
                    }

                    // Send OTP to the user's email
                    return fetch('/send-otp', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email: email })
                    });
                })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error("Failed to send OTP.");
                    }
                    return response.json();
                })
                .then((data) => {
                    console.log("OTP sent:", data);
                    window.location.href = "/otp-verification?email=" + encodeURIComponent(email); // Redirect to OTP verification
                })
                .catch((error) => {
                    console.error("Error during login or OTP sending:", error.message);
                    alert("Error: " + error.message);
                });
        });
    }
};