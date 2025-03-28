<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />
    <div class="container-fluid">
        <div class="row offset-md-2">
            <div class="col-md-8">  
            <h2 >Login</h2><br> 
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="input-group mb-3">
                    <label class="form-label w-100">{{ __('Email') }}</label>
                    <x-text-input id="email" class="block w-full form-control" type="email" placeholder="Email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2"  />              
                </div>

                <!-- Password -->
                <div class="input-group mb-4">
                    <label class="form-label w-100">{{ __('Password') }}</label>
                    <x-text-input id="password" class="block  w-full form-control" type="password" placeholder="Password" name="password" required autocomplete="current-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />    
                </div>

                <!-- Remember Me -->
                <div class="block mt-4 mb-4">
                    
                </div>

                <div class="row">
                    
                    <div class="col-6 text-start">
                    <label for="remember_me" class="inline-flex items-center">
                        <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                        <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                    </label>
                        {{-- 
                        @if (Route::has('password.request'))
                            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                        @endif 
                        --}}
                    </div>
                    <div class="col-6 text-end">
                        <x-primary-button class="btn btn-primary px-4">
                            {{ __('Log in') }}
                        </x-primary-button>
                    </div>
                </div>
            </form>
            </div>
        </div>    
    </div>
</x-guest-layout>



                  
                  
                  
                  
