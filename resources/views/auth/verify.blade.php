@extends('layouts.app')

@section('content')
<style>
    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }
    
    /* Hide navbar */
    nav.navbar {
        display: none !important;
    }
    
    #app > main {
        padding: 0 !important;
    }
    
    .auth-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 2rem 0;
    }
    
    .auth-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        max-width: 500px;
        width: 100%;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .auth-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
    }
    
    .auth-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 2.5rem 2rem;
        text-align: center;
    }
    
    .auth-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .auth-header p {
        margin: 0.5rem 0 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }
    
    .auth-body {
        padding: 2.5rem 2rem;
        text-align: center;
    }
    
    .alert-success {
        background-color: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        border: 1px solid transparent;
    }
    
    .verify-text {
        color: #666;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
    }
    
    .resend-form {
        display: inline;
    }
    
    .btn-resend {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 10px;
        padding: 0.75rem 1.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-resend:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
        text-decoration: none;
    }
    
    .btn-resend:active {
        transform: translateY(0);
    }
    
    .verify-icon {
        font-size: 4rem;
        color: #667eea;
        margin-bottom: 1.5rem;
    }
    
    @media (max-width: 576px) {
        .auth-container {
            padding: 1rem;
        }
        
        .auth-body {
            padding: 2rem 1.5rem;
        }
        
        .auth-header {
            padding: 2rem 1.5rem;
        }
    }
</style>

<div class="auth-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>{{ __('Verify Your Email Address') }}</h2>
                        <p>{{ __('Please verify your email to continue') }}</p>
                    </div>

                    <div class="auth-body">
                        <div class="verify-icon">
                            <i class="fas fa-envelope-circle-check"></i>
                        </div>

                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        <p class="verify-text">
                            {{ __('Before proceeding, please check your email for a verification link.') }}
                        </p>
                        
                        <p class="verify-text">
                            {{ __('If you did not receive the email') }},
                        </p>
                        
                        <form class="resend-form" method="POST" action="{{ route('verification.resend') }}">
                            @csrf
                            <button type="submit" class="btn-resend">
                                {{ __('click here to request another') }}
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
