@extends('layouts.app')

@section('content')
<style>
    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    /* Hide navbar on reset password page */
    nav.navbar {
        display: none !important;
    }
    
    #app > main {
        padding: 0 !important;
    }
    
    .reset-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem 0;
    }
    
    .reset-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 450px;
        width: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .reset-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
    }
    
    .reset-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        text-align: center;
    }
    
    .reset-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .reset-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    .reset-body {
        padding: 2.5rem 2rem;
    }
    
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
    }
    
    .form-label i {
        margin-left: 0.5rem;
        color: #667eea;
    }
    
    .form-control {
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 1rem;
        transition: all 0.3s ease;
        background-color: #f8f9fa;
    }
    
    .form-control:focus {
        border-color: #667eea;
        background-color: #ffffff;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
        outline: none;
    }
    
    .form-control.is-invalid {
        border-color: #dc3545;
    }
    
    .btn-reset {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 0.875rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        color: white;
        width: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }
    
    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
    }
    
    .btn-reset:active {
        transform: translateY(0);
    }
    
    .invalid-feedback {
        display: block;
        margin-top: 0.5rem;
        font-size: 0.875rem;
    }
    
    .input-group-icon {
        position: relative;
    }
    
    .input-group-icon i {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        pointer-events: none;
    }
    
    .input-group-icon .form-control {
        padding-right: 2.5rem;
    }
    
    @media (max-width: 576px) {
        .reset-container {
            padding: 1rem;
        }
        
        .reset-body {
            padding: 2rem 1.5rem;
        }
        
        .reset-header {
            padding: 2rem 1.5rem;
        }
    }
</style>

<div class="reset-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="reset-card">
                    <div class="reset-header">
                        <h2>{{ __('Reset Password') }}</h2>
                        <p>{{ __('Enter your new password below') }}</p>
                    </div>

                    <div class="reset-body">
                        <form method="POST" action="{{ route('password.update') }}">
                            @csrf

                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="form-group">
                                <label for="email" class="form-label">
                                    {{ __('Email Address') }}
                                    <i class="fas fa-envelope"></i>
                                </label>
                                <div class="input-group-icon">
                                    <input id="email" 
                                           type="email" 
                                           class="form-control @error('email') is-invalid @enderror" 
                                           name="email" 
                                           value="{{ $email ?? old('email') }}" 
                                           required 
                                           autocomplete="email" 
                                           autofocus
                                           placeholder="{{ __('Enter your email address') }}">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">
                                    {{ __('New Password') }}
                                    <i class="fas fa-lock"></i>
                                </label>
                                <div class="input-group-icon">
                                    <input id="password" 
                                           type="password" 
                                           class="form-control @error('password') is-invalid @enderror" 
                                           name="password" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="{{ __('Enter your new password') }}">
                                    <i class="fas fa-lock"></i>
                                </div>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password-confirm" class="form-label">
                                    {{ __('Confirm Password') }}
                                    <i class="fas fa-lock"></i>
                                </label>
                                <div class="input-group-icon">
                                    <input id="password-confirm" 
                                           type="password" 
                                           class="form-control" 
                                           name="password_confirmation" 
                                           required 
                                           autocomplete="new-password"
                                           placeholder="{{ __('Confirm your new password') }}">
                                    <i class="fas fa-lock"></i>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-reset">
                                {{ __('Reset Password') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
@endsection
