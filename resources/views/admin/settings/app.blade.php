@extends('admin.layout')

@section('title', 'App Settings')
@section('heading', 'App Settings')

@section('content')

<style>
    .settings-page {
        max-width: 1160px;
        margin: 0 auto;
        color: #202223;
        box-sizing: border-box;
    }

    .settings-header {
        margin-bottom: 24px;
    }

    .settings-header h2 {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 6px;
        letter-spacing: -0.5px;
    }

    .settings-header p {
        color: #6d7175;
        font-size: 13px;
        margin: 0;
    }

    .settings-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 24px;
        align-items: flex-start;
    }

    .settings-card {
        background: #fff;
        border: 1px solid #e3e5e7;
        border-radius: 12px;
        margin-bottom: 0;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
        box-sizing: border-box;
    }

    .card-heading {
        padding: 20px 24px;
        border-bottom: 1px solid #eceeef;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-heading-icon {
        width: 36px;
        height: 36px;
        border-radius: 9px;
        background: #eaf5f0;
        color: #008060;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .card-heading-icon svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .card-heading h3 {
        font-size: 15px;
        font-weight: 700;
        margin: 0 0 3px;
        color: #202223;
    }

    .card-heading p {
        color: #73777a;
        font-size: 12px;
        margin: 0;
    }

    .card-body {
        padding: 24px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 13px;
        font-weight: 650;
        margin-bottom: 7px;
        color: #303438;
    }

    .form-label .required-mark {
        color: #d72c0d;
        margin-left: 2px;
    }

    .form-control {
        width: 100%;
        min-height: 42px;
        padding: 10px 13px;
        border: 1px solid #c9cccf;
        border-radius: 7px;
        font-size: 13px;
        color: #202223;
        background: #fff;
        box-sizing: border-box;
        outline: none;
        transition: border-color .2s, box-shadow .2s;
        font-family: inherit;
    }

    .form-control:focus {
        border-color: #008060;
        box-shadow: 0 0 0 3px rgba(0, 128, 96, .10);
    }

    .form-control.is-invalid {
        border-color: #d72c0d;
        background-color: #fffaf9;
    }

    .form-control.is-invalid:focus {
        box-shadow: 0 0 0 3px rgba(215, 44, 13, .12);
    }

    .form-control:disabled,
    .form-control[readonly] {
        background: #f6f6f7;
        color: #6d7175;
        cursor: not-allowed;
        border-color: #e1e3e5;
    }

    .form-error {
        display: block;
        margin-top: 6px;
        color: #d72c0d;
        font-size: 12px;
        font-weight: 500;
    }

    .form-help {
        display: block;
        margin-top: 6px;
        color: #8c9196;
        font-size: 11px;
        line-height: 1.45;
    }

    /* Password Input with Show/Hide Toggle */
    .password-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .password-wrapper .form-control {
        padding-right: 44px;
    }

    .btn-toggle-password {
        position: absolute;
        right: 1px;
        top: 1px;
        bottom: 1px;
        width: 42px;
        background: transparent;
        border: 0;
        color: #6d7175;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0 6px 6px 0;
        transition: color .15s ease-in-out;
        padding: 0;
    }

    .btn-toggle-password:hover {
        color: #202223;
    }

    .btn-toggle-password svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .settings-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 16px 24px;
        background: #fafbfb;
        border-top: 1px solid #eceeef;
    }

    .footer-note {
        color: #73777a;
        font-size: 11px;
        line-height: 1.4;
        flex: 1;
    }

    .btn-save {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 18px;
        border: 0;
        border-radius: 7px;
        background: #008060;
        color: #fff;
        font-size: 13px;
        font-weight: 650;
        cursor: pointer;
        transition: background .2s, box-shadow .2s;
        font-family: inherit;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .btn-save:hover {
        background: #006e52;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
    }

    .btn-save:active {
        transform: translateY(1px);
    }

    .btn-save svg {
        width: 15px;
        height: 15px;
        fill: none;
        stroke: currentColor;
        stroke-width: 2.2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .alert-success {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 13px;
        font-weight: 500;
        background: #eaf6f0;
        color: #087a53;
        border: 1px solid #c6ead8;
    }

    .alert-success svg {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        stroke: currentColor;
        stroke-width: 2.5;
        fill: none;
    }

    .readonly-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 600;
        color: #6d7175;
        background: #f1f2f3;
        padding: 2px 7px;
        border-radius: 4px;
        margin-left: 6px;
    }

    @media (max-width: 960px) {
        .settings-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 600px) {
        .settings-header h2 {
            font-size: 19px;
        }

        .card-heading,
        .card-body,
        .settings-footer {
            padding: 16px;
        }

        .settings-footer {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .btn-save {
            width: 100%;
        }
    }
</style>

<div class="settings-page">

    <div class="settings-header">
        <h2>App Settings</h2>
        <p>Manage your administrator profile information and account security credentials.</p>
    </div>

    <div class="settings-grid">

        {{-- Left Column: Profile Information Card --}}
        <div class="settings-card" id="profile-card">
            <div class="card-heading">
                <div class="card-heading-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </div>
                <div>
                    <h3>Profile Information</h3>
                    <p>Update your administrator account name and primary identification.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.settings.app.profile') }}">
                @csrf
                @method('PUT')

                <div class="card-body">
                    @if(session('profile_success'))
                    <div class="alert-success" role="alert">
                        <svg viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>{{ session('profile_success') }}</span>
                    </div>
                    @endif

                    <div class="form-group">
                        <label for="admin_name" class="form-label">
                            Administrator Name
                            <span class="required-mark">*</span>
                        </label>
                        <input
                            type="text"
                            id="admin_name"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $admin->name) }}"
                            required
                            maxlength="255"
                            placeholder="e.g. Administrator">
                        @error('name')
                        <span class="form-error">{{ $message }}</span>
                        @enderror
                        <span class="form-help">Displayed in the sidebar, topbar, and administrative audit logs.</span>
                    </div>

                    <div class="form-group">
                        <label for="admin_email" class="form-label">
                            Administrator Email
                            <span class="readonly-badge">Read-only</span>
                        </label>
                        <input
                            type="email"
                            id="admin_email"
                            name="email"
                            class="form-control"
                            value="{{ $admin->email }}"
                            readonly
                            disabled>
                        <span class="form-help">Your login email address is permanently assigned to your administrator account for security.</span>
                    </div>
                </div>

                <div class="settings-footer">
                    <span class="footer-note">Changes apply immediately to your active administrator session.</span>
                    <button type="submit" class="btn-save">
                        <svg viewBox="0 0 24 24">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Save Changes</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Right Column: Change Password Card --}}
        <div class="settings-card" id="password-card">
            <div class="card-heading">
                <div class="card-heading-icon" style="background: #eef2ff; color: #4f46e5;">
                    <svg viewBox="0 0 24 24">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <div>
                    <h3>Change Password</h3>
                    <p>Ensure your account is protected with a strong, complex password.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.settings.app.password') }}">
                @csrf
                @method('PUT')

                <div class="card-body">
                    @if(session('password_success'))
                    <div class="alert-success" role="alert">
                        <svg viewBox="0 0 24 24">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>{{ session('password_success') }}</span>
                    </div>
                    @endif

                    <div class="form-group">
                        <label for="current_password" class="form-label">
                            Current Password
                            <span class="required-mark">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-control @error('current_password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your current password">
                            <button type="button" class="btn-toggle-password" data-target="current_password" aria-label="Toggle current password visibility" title="Show or hide password">
                                <svg class="eye-icon" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" viewBox="0 0 24 24" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        @error('current_password')
                        <span class="form-error">{{ $message }}</span>
                        @enderror
                        <span class="form-help">Enter your existing account password to confirm authorization.</span>
                    </div>

                    <div class="form-group">
                        <label for="new_password" class="form-label">
                            New Password
                            <span class="required-mark">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="new_password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                                placeholder="Enter a new secure password">
                            <button type="button" class="btn-toggle-password" data-target="new_password" aria-label="Toggle new password visibility" title="Show or hide password">
                                <svg class="eye-icon" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" viewBox="0 0 24 24" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        @error('password')
                        <span class="form-error">{{ $message }}</span>
                        @enderror
                        <span class="form-help">Must be at least 8 characters long and contain both letters and numbers.</span>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">
                            Confirm New Password
                            <span class="required-mark">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                class="form-control @error('password_confirmation') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                                placeholder="Repeat the new password">
                            <button type="button" class="btn-toggle-password" data-target="password_confirmation" aria-label="Toggle password confirmation visibility" title="Show or hide password">
                                <svg class="eye-icon" viewBox="0 0 24 24">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg class="eye-off-icon" viewBox="0 0 24 24" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation')
                        <span class="form-error">{{ $message }}</span>
                        @enderror
                        <span class="form-help">Must match the new password entered above.</span>
                    </div>
                </div>

                <div class="settings-footer">
                    <span class="footer-note">Your active administrator session will remain logged in after updating.</span>
                    <button type="submit" class="btn-save" style="background: #4f46e5;">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <span>Update Password</span>
                    </button>
                </div>
            </form>
        </div>

    </div>

</div>

@push('scripts')
<script>
    (function () {
        'use strict';

        document.querySelectorAll('.btn-toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                if (!input) return;

                const eyeIcon = this.querySelector('.eye-icon');
                const eyeOffIcon = this.querySelector('.eye-off-icon');

                if (input.type === 'password') {
                    input.type = 'text';
                    if (eyeIcon) eyeIcon.style.display = 'none';
                    if (eyeOffIcon) eyeOffIcon.style.display = 'inline-block';
                    this.setAttribute('aria-label', 'Hide password');
                } else {
                    input.type = 'password';
                    if (eyeIcon) eyeIcon.style.display = 'inline-block';
                    if (eyeOffIcon) eyeOffIcon.style.display = 'none';
                    this.setAttribute('aria-label', 'Show password');
                }
            });
        });
    })();
</script>
@endpush

@endsection
