<x-guest-layout>
    <div class="max-w-md mx-auto my-8 p-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold mb-6 text-center">OTP Verification</h2>
        
        <form id="otp-verification-form">
            <input type="hidden" id="email" value="{{ request()->query('email') }}">
            
            <div class="mb-4">
                <label for="otp" class="block text-sm font-medium text-gray-700 mb-1">Enter OTP sent to your email:</label>
                <input type="text" id="otp" name="otp" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="mt-2 text-sm text-gray-600">
                    <p>OTP expires in <span id="countdown">5:00</span></p>
                </div>
            </div>
            
            <div class="flex justify-between items-center mt-6">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Verify OTP
                </button>
                
                <button type="button" id="resend-otp" class="text-blue-500 hover:text-blue-700">
                    Resend OTP
                </button>
            </div>
        </form>

        <script>
            document.getElementById('otp-verification-form').addEventListener('submit', function (e) {
                e.preventDefault();
                
                const email = document.getElementById('email').value;
                const otp = document.getElementById('otp').value;
                
                fetch('/verify-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email: email, otp: otp })
                })
                .then((response) => response.json())
                .then((data) => {
                    if (data.message === 'OTP verified successfully!') {
                        window.location.href = "/dashboard"; // Redirect to dashboard
                    } else {
                        alert(data.error || data.message); // Show error message
                    }
                })
                .catch((error) => {
                    console.error("Error during OTP verification:", error);
                    alert("Error: " + error.message);
                });
            });
            
            // Add resend OTP functionality
            document.getElementById('resend-otp').addEventListener('click', function() {
                const email = document.getElementById('email').value;
                
                fetch('/resend-otp', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ email: email })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.message === 'New OTP sent to your email.') {
                        alert(data.message); // Show success message
                        resetCountdown(); // Reset the countdown timer
                        document.getElementById('otp').disabled = false; // Re-enable OTP input if disabled
                    } else {
                        alert(data.error || data.message); // Show error message
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to resend OTP. Please try again.');
                });
            });
            
            // Countdown timer functionality
            let timer;
            
            function startCountdown() {
                let timeLeft = 5 * 60; // 5 minutes in seconds
                const countdownElement = document.getElementById('countdown');
                
                // Clear any existing timer
                if (timer) clearInterval(timer);
                
                timer = setInterval(function() {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    
                    countdownElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                    
                    if (--timeLeft < 0) {
                        clearInterval(timer);
                        countdownElement.textContent = 'Expired';
                        document.getElementById('otp').disabled = true;
                        alert('OTP has expired. Please click "Resend OTP" to get a new one.');
                    }
                }, 1000);
            }
            
            function resetCountdown() {
                // Reset and restart the countdown
                startCountdown();
            }
            
            // Start the countdown when the page loads
            document.addEventListener('DOMContentLoaded', startCountdown);
        </script>
    </div>
</x-guest-layout>