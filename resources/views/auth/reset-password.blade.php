@extends('layouts.app')

@section('title', 'Create Password')

@section('content')
<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-7 col-xl-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-shield-lock fs-4" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h4 class="mb-1">Create Your Password</h4>
                            <p class="text-muted mb-0">Choose a secure password to activate your Resource Team account.</p>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">

                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" placeholder="Enter your e-mail address" autocomplete="email" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Enter a new password" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle="password" aria-label="Show password">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Use at least 8 characters.</div>
                        </div>

                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Re-enter your new password" autocomplete="new-password" required>
                                <button class="btn btn-outline-secondary" type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Create Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const icon = button.querySelector('i');
            const showingPassword = input.type === 'text';

            input.type = showingPassword ? 'password' : 'text';
            icon.classList.toggle('bi-eye', showingPassword);
            icon.classList.toggle('bi-eye-slash', !showingPassword);
            button.setAttribute('aria-label', showingPassword ? 'Show password' : 'Hide password');
        });
    });
</script>
@endpush
@endsection
