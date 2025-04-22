<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Login - Webhook GPT Admin Panel</title>
    
    <!-- Fonts and icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Material Dashboard CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/material-dashboard.min.css') }}">
    <style>
        .login-page {
            background-image: url('{{ asset('assets/img/login-bg.jpg') }}');
            background-size: cover;
            background-position: center;
        }
        .login-card {
            max-width: 450px;
            margin: 8% auto;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card login-card z-index-0 mt-5">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                            <div class="login-header">
                                <h1 class="text-white font-weight-bolder">Webhook GPT</h1>
                                <p class="text-white">Admin Panel</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        @if($errors->any())
                        <div class="alert alert-danger text-white font-weight-bold">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        <form role="form" method="POST" action="{{ route('login') }}">
                            @csrf
                            
                            <div class="input-group input-group-outline mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
                            </div>
                            
                            <div class="input-group input-group-outline mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="form-check form-switch d-flex align-items-center mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label mb-0 ms-3" for="remember">Ingat Saya</label>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn bg-gradient-primary w-100 my-4 mb-2">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Core JS Files -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    
    <script>
        // Form focus effect for material design
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.input-group-outline input');
            
            inputs.forEach(input => {
                if (input.value) {
                    input.parentElement.classList.add('is-filled');
                }
                
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('is-focused');
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('is-focused');
                    if (this.value) {
                        this.parentElement.classList.add('is-filled');
                    } else {
                        this.parentElement.classList.remove('is-filled');
                    }
                });
            });
        });
    </script>
</body>
</html>