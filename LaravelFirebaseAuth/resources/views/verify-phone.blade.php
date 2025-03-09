<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Please verify your phone number to complete the registration process.') }}
    </div>

    <!-- Phone Number Form -->
    <div id="phone-form">
        <form>
            @csrf
            
            <!-- Phone Number Input -->
            <div>
                <x-input-label for="phone-number" :value="__('Phone Number')" />
                <x-text-input id="phone-number" class="block mt-1 w-full" type="text" name="phone" required autofocus placeholder="+63XXXXXXXXXX" />
                <div class="mt-1 text-sm text-gray-500">Enter a valid Philippine phone number (e.g., +639123456789)</div>
            </div>
            
            <!-- reCAPTCHA Container -->
            <div id="recaptcha-container" class="mt-4"></div>
            
            <div class="flex items-center justify-end mt-4">
                <x-primary-button type="button" id="send-otp" class="ms-3">
                    {{ __('Send OTP') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- OTP Verification Form (Initially Hidden) -->
    <div id="otp-form" style="display: none;">
        <form>
            @csrf
            
            <!-- OTP Input -->
            <div>
                <x-input-label for="otp-code" :value="__('OTP Code')" />
                <x-text-input id="otp-code" class="block mt-1 w-full" type="text" name="otp-code" required autofocus placeholder="Enter 6-digit code" />
                <div class="mt-1 text-sm text-gray-500">Please enter the 6-digit code sent to your phone</div>
            </div>
            
            <div class="flex items-center justify-end mt-4">
                <x-primary-button type="button" id="verify-otp" class="ms-3">
                    {{ __('Verify OTP') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
