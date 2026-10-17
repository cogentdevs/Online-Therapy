<div class="modal fade subscription-login-modal" id="subscription-login-modal" tabindex="-1"
    aria-labelledby="subscription-login-modal-title" aria-hidden="true" data-subscription-login-modal
    data-subscription-clear-intent-url="{{ route('front.subscriptions.intent.clear') }}"
    data-paid-content-clear-intent-url="{{ route('front.paid-content.intent.clear') }}"
    data-auto-open="{{ session('show_front_login_modal') ? 'true' : 'false' }}" dir="ltr">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="subscription-login-modal-title">Log In</h2>
                    <p>Log in to your account to continue with your subscription.</p>
                </div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if (session('front_login_modal_message'))
                    <div class="alert alert-info" role="status">{{ session('front_login_modal_message') }}</div>
                @endif
                <div class="alert alert-danger d-none" role="alert" data-subscription-login-error></div>
                <form class="front-auth-form subscription-login-form" method="post" action="{{ route('front.login.store') }}"
                    data-subscription-login-form novalidate>
                    @csrf
                    <div class="front-auth-field">
                        <label for="subscription-login-email">Email Address</label>
                        <div class="front-auth-control">
                            <input id="subscription-login-email" name="email" type="email" autocomplete="email"
                                inputmode="email" placeholder="Enter your email address" required dir="ltr">
                            <i class="fa-regular fa-envelope front-auth-field__icon" aria-hidden="true"></i>
                        </div>
                        <div class="subscription-login-form__field-error" data-error-for="email"></div>
                    </div>
                    <div class="front-auth-field">
                        <label for="subscription-login-password">Password</label>
                        <div class="front-auth-control front-auth-control--password">
                            <input id="subscription-login-password" name="password" type="password"
                                autocomplete="current-password" placeholder="Enter your password" required dir="ltr">
                            <i class="fa-solid fa-lock front-auth-field__icon" aria-hidden="true"></i>
                            <button class="front-auth-password-toggle" type="button" data-front-password-toggle
                                aria-controls="subscription-login-password" aria-pressed="false" aria-label="Show password"
                                data-show-label="Show password" data-hide-label="Hide password">
                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="subscription-login-form__field-error" data-error-for="password"></div>
                    </div>
                    <div class="front-auth-options">
                        <label class="front-auth-remember" for="subscription-login-remember">
                            <input id="subscription-login-remember" name="remember" type="checkbox" value="1">
                            <span>Remember Me</span>
                        </label>
                        <a class="front-auth-forgot" href="{{ route('front.password.request') }}">Forgot Password?</a>
                    </div>
                    <button class="front-auth-submit" type="submit" data-subscription-login-submit>
                        Log In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                    <p class="front-auth-switch">Don't have an account? <a href="{{ route('front.register') }}">Register Now</a></p>
                </form>
            </div>
        </div>
    </div>
</div>