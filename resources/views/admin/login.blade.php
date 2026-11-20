
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Login | Socialvire</title>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary: #008060;
            --primary-dark: #006e52;
            --text: #202223;
            --muted: #6d7175;
            --border: #e1e3e5;
            --background: #f6f6f7;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background:
                radial-gradient(circle at 50% 0%, #e7f3ee 0, transparent 45%),
                var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .login-wrapper {
            width: 100%;
            max-width: 390px;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
        }

        .brand-icon {
            width: 37px;
            height: 37px;
            display: grid;
            place-items: center;
            background: var(--primary);
            color: white;
            border-radius: 10px;
            font-size: 19px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(0, 128, 96, 0.2);
        }

        .brand-name {
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-name span {
            color: var(--primary);
        }

        .login-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 13px;
            padding: 30px;
            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.025),
                0 12px 35px rgba(0, 0, 0, 0.045);
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 9px;
            border-radius: 5px;
            background: #eaf5f0;
            color: #006e52;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .security-badge svg {
            width: 13px;
            height: 13px;
        }

        h1 {
            margin: 0;
            font-size: 25px;
            line-height: 1.3;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .subtitle {
            margin: 8px 0 25px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-label {
            display: block;
            margin-bottom: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            height: 43px;
            padding: 0 13px;
            border: 1px solid #c9cccf;
            border-radius: 7px;
            outline: none;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 12px;
            transition: border-color .2s, box-shadow .2s;
        }

        .input-wrapper input::placeholder {
            color: #9ca0a4;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 128, 96, 0.12);
        }

        .password-input {
            padding-right: 45px !important;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: 0;
            background: none;
            color: #73777a;
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
        }

        .toggle-password svg {
            width: 17px;
            height: 17px;
        }

        .form-error {
            margin-top: 6px;
            color: #d72c0d;
            font-size: 11px;
            line-height: 1.5;
        }

        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 2px 0 20px;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 7px;
            color: #55595c;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
        }

        .remember input {
            width: 14px;
            height: 14px;
            margin: 0;
            accent-color: var(--primary);
        }

        .submit-btn {
            width: 100%;
            height: 44px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 9px;
            border: 0;
            border-radius: 7px;
            background: var(--primary);
            color: #fff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .2s;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .submit-btn:active {
            transform: scale(.99);
        }

        .submit-btn svg {
            width: 16px;
            height: 16px;
        }

        .divider {
            height: 1px;
            background: #eeeeef;
            margin: 22px 0 17px;
        }

        .security-note {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #8c9196;
            font-size: 10px;
            text-align: center;
        }

        .security-note svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            color: #92969a;
            font-size: 10px;
        }

        .footer strong {
            color: #606467;
            font-weight: 700;
        }

        @media (max-width: 420px) {
            .login-card {
                padding: 24px 20px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

    <main class="login-wrapper">

        <div class="brand">
            <div class="brand-icon">S</div>
            <div class="brand-name">social<span>vire</span></div>
        </div>

        <section class="login-card">

            <div class="security-badge">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="10" width="16" height="11" rx="2"/>
                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>
                Secure Admin Access
            </div>

            <h1>Welcome back</h1>

            <p class="subtitle">
                Sign in to your Socialvire control panel and manage your application.
            </p>

            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">
                        Email address
                    </label>

                    <div class="input-wrapper">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="admin@example.com"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    @error('email')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                    </label>

                    <div class="input-wrapper">
                        <input
                            id="password"
                            class="password-input"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >

                        <button
                            class="toggle-password"
                            type="button"
                            id="togglePassword"
                            aria-label="Show password"
                        >
                            <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>

                    @error('password')
                        <div class="form-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-options">
                    <label class="remember">
                        <input type="checkbox" name="remember" value="1">
                        Remember me
                    </label>
                </div>

                <button class="submit-btn" type="submit">
                    Sign in to dashboard

                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>

            </form>

            <div class="divider"></div>

            <div class="security-note">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>

                Protected admin environment
            </div>

        </section>

        <footer class="footer">
            &copy; {{ date('Y') }} <strong>Socialvire</strong>.
            All rights reserved.
        </footer>

    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';

            passwordInput.type = isPassword ? 'text' : 'password';

            togglePassword.setAttribute(
                'aria-label',
                isPassword ? 'Hide password' : 'Show password'
            );
        });
    </script>

</body>
</html>
