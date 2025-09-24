@extends('auth.layouts.authentication')

@section('content')
    <div class="aiz-main-wrapper d-flex flex-column justify-content-md-center bg-white">
        <section class="bg-white overflow-hidden">
            <div class="row">
                <div class="col-xxl-6 col-xl-9 col-lg-10 col-md-7 mx-auto py-lg-4">
                    <div class="card shadow-none rounded-0 border-0">
                        <div class="row no-gutters">
                            <!-- Left Side Image-->
                            <div class="col-lg-6">
                                <img src="{{ uploaded_asset(get_setting('customer_register_page_image')) }}"
                                     alt="{{ translate('Customer Register Page Image') }}" class="img-fit h-100">
                            </div>

                            <!-- Right Side -->
                            <div
                                class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center border right-content"
                                style="height: auto;">
                                <!-- Site Icon -->
                                <div class="size-48px mb-3 mx-auto mx-lg-0">
                                    <img src="{{ uploaded_asset(get_setting('site_icon')) }}"
                                         alt="{{ translate('Site Icon')}}" class="img-fit h-100">
                                </div>

                                <!-- Titles -->
                                <div class="text-center text-lg-left">
                                    <h1 class="fs-20 fs-md-24 fw-700 text-primary"
                                        style="text-transform: uppercase;">{{ translate('Create an account')}}</h1>
                                </div>

                                <!-- Register form -->
                                <div class="pt-3">
                                    <div class="">
                                        <form id="reg-form" class="form-default" role="form"
                                              action="{{ route('register') }}" method="POST">
                                            @csrf
                                            <!-- Name -->
                                            <div class="form-group">
                                                <label for="name"
                                                       class="fs-12 fw-700 text-soft-dark">{{  translate('Full Name') }}</label>
                                                <input type="text"
                                                       class="form-control rounded-0{{ $errors->has('name') ? ' is-invalid' : '' }}"
                                                       value="{{ old('name') }}"
                                                       placeholder="{{  translate('Full Name') }}" name="name">
                                                @if ($errors->has('name'))
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $errors->first('name') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Email or Phone -->
                                            @if (addon_is_activated('otp_system'))
                                                <div class="form-group phone-form-group mb-1">
                                                    <label for="phone"
                                                           class="fs-12 fw-700 text-soft-dark">{{  translate('Phone') }}</label>
                                                    <input type="tel" id="phone-code"
                                                           class="form-control rounded-0{{ $errors->has('phone') ? ' is-invalid' : '' }}"
                                                           value="{{ old('phone') }}" placeholder="" name="phone"
                                                           autocomplete="off">
                                                </div>

                                                <input type="hidden" name="country_code" value="">

                                                <div class="form-group email-form-group mb-1 d-none">
                                                    <label for="email"
                                                           class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email"
                                                           class="form-control rounded-0 {{ $errors->has('email') ? ' is-invalid' : '' }}"
                                                           value="{{ old('email') }}"
                                                           placeholder="{{  translate('Email') }}" name="email"
                                                           autocomplete="off">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="form-group text-right">
                                                    <button class="btn btn-link p-0 text-primary" type="button"
                                                            onclick="toggleEmailPhone(this)">
                                                        <i>*{{ translate('Use Email Instead') }}</i></button>
                                                </div>
                                            @else
                                                <div class="form-group">
                                                    <label for="email"
                                                           class="fs-12 fw-700 text-soft-dark">{{  translate('Email') }}</label>
                                                    <input type="email"
                                                           class="form-control rounded-0{{ $errors->has('email') ? ' is-invalid' : '' }}"
                                                           value="{{ old('email') }}"
                                                           placeholder="{{  translate('Email') }}" name="email">
                                                    @if ($errors->has('email'))
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $errors->first('email') }}</strong>
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- password -->
                                            <div class="form-group mb-0">
                                                <label for="password"
                                                       class="fs-12 fw-700 text-soft-dark">{{  translate('Password') }}</label>
                                                <div class="position-relative">
                                                    <input type="password"
                                                           class="form-control rounded-0{{ $errors->has('password') ? ' is-invalid' : '' }}"
                                                           placeholder="{{  translate('Password') }}" name="password">
                                                    <i class="password-toggle las la-2x la-eye"></i>
                                                </div>
                                                <div class="text-right mt-1">
                                                    <span
                                                        class="fs-12 fw-400 text-gray-dark">{{ translate('Password must contain at least 6 digits') }}</span>
                                                </div>
                                                @if ($errors->has('password'))
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $errors->first('password') }}</strong>
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- password Confirm -->
                                            <div class="form-group">
                                                <label for="password_confirmation"
                                                       class="fs-12 fw-700 text-soft-dark">{{  translate('Confirm Password') }}</label>
                                                <div class="position-relative">
                                                    <input type="password" class="form-control rounded-0"
                                                           placeholder="{{  translate('Confirm Password') }}"
                                                           name="password_confirmation">
                                                    <i class="password-toggle las la-2x la-eye"></i>
                                                </div>
                                            </div>

                                            <!-- Recaptcha -->
                                            @if(get_setting('google_recaptcha') == 1)
                                                <div class="form-group">
                                                    <div class="g-recaptcha"
                                                         data-sitekey="{{ env('CAPTCHA_KEY') }}"></div>
                                                </div>
                                                @if ($errors->has('g-recaptcha-response'))
                                                    <span class="invalid-feedback" role="alert" style="display: block;">
                                                        <strong>{{ $errors->first('g-recaptcha-response') }}</strong>
                                                    </span>
                                                @endif
                                            @endif
                                            <div class="form-group">
                                                <select name="gender" class="form-control">
                                                    <option value="" disabled
                                                            selected>{{ translate("Gender") }}</option>
                                                    <option value="male">{{ translate("Male") }}</option>
                                                    <option value="female">{{ translate("Female") }}</option>
                                                </select>
                                            </div>

                                            <!-- Terms and Conditions -->
                                            <div class="mb-3">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" name="checkbox_example_1" required>
                                                    <span class="">{{ translate('By signing up you agree to our ')}} <a
                                                            href="{{ route('terms') }}"
                                                            class="fw-500">{{ translate('terms and conditions.') }}</a></span>
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mb-4 mt-4">
                                                <button type="submit"
                                                        class="btn btn-primary btn-block fw-600 rounded-0">{{  translate('Create Account') }}</button>
                                            </div>
                                        </form>

{{--                                        <hr style="border-color: #0b60bd !important;">--}}
{{--                                        <div class="text-center mb-3">--}}
{{--                                            <span class="bg-white fs-12 text-gray">{{ translate('or')}}</span>--}}
{{--                                        </div>--}}
{{--                                        <hr style="border-color: #0b60bd !important;">--}}
                                        <div id="login_with_media_container">
                                            <a href="{{ route('social.login', ['provider' => 'google']) }}"
                                               class="social-login-btn google">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"
                                                     class="icon-svg">
                                                    <path
                                                        d="M16.318 13.714v5.484h9.078c-0.37 2.354-2.745 6.901-9.078 6.901-5.458 0-9.917-4.521-9.917-10.099s4.458-10.099 9.917-10.099c3.109 0 5.193 1.318 6.38 2.464l4.339-4.182c-2.786-2.599-6.396-4.182-10.719-4.182-8.844 0-16 7.151-16 16s7.156 16 16 16c9.234 0 15.365-6.49 15.365-15.635 0-1.052-0.115-1.854-0.255-2.651z">
                                                    </path>
                                                </svg>
                                                <span>Google</span>
                                            </a>

                                            <a href="{{ route('social.login', ['provider' => 'apple']) }}"
                                               class="social-login-btn apple">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"
                                                     class="icon-svg">
                                                    <path
                                                        d="M318.7 268.7c-.3-37.5 16.5-65.9 51.1-87.1-19.2-27.6-47.9-43-86.3-46-36.3-2.8-75.3 21.4-88.9 21.4-14 0-47-20.4-72.8-20-55.9.8-115.5 41.2-115.5 124.5 0 26.5 4.9 53.7 14.7 81.7 13.1 38.2 61.2 131.6 111.4 129.4 22.7-.9 39-16.3 68.3-16.3 28.8 0 43.7 16.3 72.9 16 50.7-.4 93.8-83.4 106.3-121.9-66.9-31.7-62.5-92.7-62.2-100.7zM266.6 72.8c25.3-30.7 23-58.6 22.3-68.8-21.8 1-47 14.5-61.9 32.5-15.9 19.1-26.8 44.4-24 70.2 24.3 1.9 47.3-11.7 63.6-33.9z">
                                                    </path>
                                                </svg>
                                                <span>Apple</span>
                                            </a>
                                        </div>

                                        <!-- Social Login -->
                                        @if(get_setting('google_login') == 1 || get_setting('facebook_login') == 1 || get_setting('twitter_login') == 1 || get_setting('apple_login') == 1)
                                            <div class="text-center mb-3">
                                                <span
                                                    class="bg-white fs-12 text-gray">{{ translate('Or Join With')}}</span>
                                            </div>
                                            <ul class="list-inline social colored text-center mb-4">
                                                @if (get_setting('facebook_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'facebook']) }}"
                                                           class="facebook">
                                                            <i class="lab la-facebook-f"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if(get_setting('google_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'google']) }}"
                                                           class="google">
                                                            <i class="lab la-google"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('twitter_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'twitter']) }}"
                                                           class="twitter">
                                                            <i class="lab la-twitter"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                                @if (get_setting('apple_login') == 1)
                                                    <li class="list-inline-item">
                                                        <a href="{{ route('social.login', ['provider' => 'apple']) }}"
                                                           class="apple">
                                                            <i class="lab la-apple"></i>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif
                                    </div>

                                    <!-- Log In -->
                                    <p class="fs-12 text-gray mb-0">
                                        {{ translate('Already have an account?')}}
                                        <a href="{{ route('user.login') }}"
                                           class="ml-2 fs-14 fw-700 animate-underline-primary">{{ translate('Log In')}}</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <!-- Go Back -->
                        <div class="mt-3 mr-4 mr-md-0">
                            <a href="{{ url()->previous() }}"
                               class="ml-auto fs-14 fw-700 d-flex align-items-center text-primary"
                               style="max-width: fit-content;">
                                <i class="las la-arrow-left fs-20 mr-1"></i>
                                {{ translate('Back to Previous Page')}}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('script')
    @if(get_setting('google_recaptcha') == 1)
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif

    <script type="text/javascript">
        @if(get_setting('google_recaptcha') == 1)
        // making the CAPTCHA  a required field for form submission
        $(document).ready(function () {
            $("#reg-form").on("submit", function (evt) {
                var response = grecaptcha.getResponse();
                if (response.length == 0) {
                    //reCaptcha not verified
                    alert("please verify you are human!");
                    evt.preventDefault();
                    return false;
                }
                //captcha verified
                //do the rest of your validations here
                $("#reg-form").submit();
            });
        });
        @endif
    </script>
@endsection
