<x-guest-layout>
    <style>
        body {
            background: linear-gradient(135deg, #5f5ce6, #6a5acd);
            height: 100vh;
        }

        .login-container {
            height: 100vh;
        }

        .left-panel {
            background: linear-gradient(135deg, #5f5ce6, #6a5acd);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 600;
        }

        .login-card {
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 550px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .form-control {
            height: 45px;
            border-radius: 8px;
        }

        .btn-primary {
            background: #5f5ce6;
            border: none;
            border-radius: 8px;
            height: 45px;
        }

        .btn-primary:hover {
            background: #4b49c4;
        }

        ul {
            list-style: none;
            /* dot hata dega */
            padding-left: 0;
            margin: 0;
        }

        ul li {
            color: #dc3545;
            /* red color */
            font-size: 14px;
            margin-top: 5px;
        }

       .custom-btn {
  background-color: #f97316 !important;  /* CTA orange */
  border-color: #f97316 !important;
  color: #fff;
}

.custom-btn:hover {
  background-color: #ea580c !important;  /* darker orange */
  border-color: #ea580c !important;
  color: #fff !important;
}
    </style>

    <div class="container-fluid login-container">
        <div class="row h-100">

            <!-- Left Side -->
            <!-- <div class="col-md-6 left-panel d-none d-md-flex">
            Localists Login
        </div> -->

            <!-- Right Side -->
            <div class="col-md-12 d-flex justify-content-center align-items-md-center align-items-start pt-4 pt-md-0">
                <div class="login-card">
                   <h4 class="mb-4 text-center fw-semibold">
 👋Hey Admin! Please log in below!
</h4>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- Email -->
                        <div class="mb-3">
                            <label style="color: black;">Email</label>
                            <x-text-input
                                id="email"
                                class="form-control"
                                type="email"
                                name="email"
                                :value="old('email')"
                                placeholder="Please enter your email"
                                required autofocus />
                            <x-input-error :messages="$errors->get('email')" />
                        </div>

                        <!-- Password -->
                        <div class="mb-3 position-relative">
                            <label style="color: black;">Password</label>

                            <x-text-input
                                id="password"
                                class="form-control pe-5"
                                type="password"
                                name="password"
                                placeholder="Please enter your password"
                                required />

                            <!-- Eye Icon -->
                            <span
                                onclick="togglePassword()"
                                style="position: absolute; top: 38px; right: 15px; cursor: pointer;">
                                <i id="eyeIcon" class="bi bi-eye"></i>
                            </span>

                            <x-input-error :messages="$errors->get('password')" />
                        </div>

                        <!-- Remember -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <label style="color: black;">
                                <input type="checkbox" name="remember" > Remember me
                            </label>
                        </div>

                        <!-- Button -->
                        <div class="d-grid">
                            <button class="btn custom-btn">
                                Log in
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-guest-layout>