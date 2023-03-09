@extends('layout')

@section('title', __('Password reset'))

@section('description', __('Enter your email address below to get a new password.'))

@section('breadcrumb')
    {!! $breadcrumb !!}
@endsection

@section('content')
    <div class="container">
        <div class="row">
            <div class="col col-12 col-md-6 offset-md-3 col-lg-4 offset-lg-4">
                <div class="row mb-2">
                    <x-breadcrumb>
                        <x-breadcrumb-item :text="__('Home')" :link="route('home.index')" />
                        <x-breadcrumb-item :text="__('Password reset')" :active="true" />
                    </x-breadcrumb>
                </div>
                <div class="row mb-3">
                    <div class="col col-12">
                        <h1>@yield('title')</h1>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col col-12">
                        <p class="text-secondary">Enter your email address below to get a new password. Once you are
                            logged, you can modify
                            it.</p>
                    </div>
                </div>
                @if ($errors->any() && !$errors->has('email'))
                    <div class="alert alert-warning">
                        {{ $errors->first() }}
                    </div>
                @endif
                <div class="row">
                    <div class="col col-12">
                        <form id="password-reset-form" method="POST" action="{{ route('password.email') }}">
                            @csrf
                            <x-input type="email" id="email" name="email" :label="__('Email address')" required autofocus autocomplete="email" :value="old('email')" />
                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary float-end g-recaptcha" data-sitekey="{{ $googleRecaptchaV3SiteKey }}" data-callback="onSubmit" data-action="submit">Reset</button>
                                <a href="{{ route('login') }}" class="btn btn-link">Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- Google Recaptcha V3 --}}
    <script>
        function onSubmit(token) {
            document.getElementById("password-reset-form").submit();
        }
    </script>
@endsection

{{--
    <x-guest-layout>
<x-auth-card>
    <x-slot name="logo">
        <a href="/">
            <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
        </a>
    </x-slot>

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <!-- Validation Errors -->
    <x-auth-validation-errors class="mb-4" :errors="$errors" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-label for="email" :value="__('Email')" />

            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                autofocus />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-button>
                {{ __('Email Password Reset Link') }}
            </x-button>
        </div>
    </form>
</x-auth-card>
</x-guest-layout>
--}}
