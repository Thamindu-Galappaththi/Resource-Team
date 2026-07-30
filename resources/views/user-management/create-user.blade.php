@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="container-fluid py-5" style="min-height: calc(100vh - 80px);">
    <div class="row justify-content-center">
        <div class="col-xl-7 col-lg-8 col-md-10">
            <div class="card border-0 rounded-4 shadow-lg" style="background: rgba(255,255,255,0.96); backdrop-filter: blur(10px);">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold">User Registration</h2>
                        <p class="text-muted mb-0">Enter details to provision a new institutional resource account.</p>
                    </div>

                    @if(session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('create.user.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg" value="{{ old('name') }}" placeholder="e.g. John Doe" required>
                        </div>

                        <div class="row g-3 mb-1">
                            <div class="col-md-6">
                                <label for="service_id" class="form-label">Service Id</label>
                                <input type="text" name="service_id" id="service_id" class="form-control form-control-lg" value="{{ old('service_id') }}" placeholder="SID-XXXX">
                            </div>
                            <div class="col-md-6">
                                <label for="nic" class="form-label">NIC (National Identity Card)</label>
                                <input type="text" name="nic" id="nic" class="form-control form-control-lg" value="{{ old('nic') }}" placeholder="XXXXXXXXX" required>
                                <small class="text-muted">Note: NIC is used as the user name.</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg" value="{{ old('email') }}" placeholder="user@nebula.edu" required>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location" class="form-select form-select-lg">
                                <option value="">Select location</option>
                                <option value="Nebula Institute of Technology - Welisara" {{ old('location') === 'Nebula Institute of Technology - Welisara' ? 'selected' : '' }}>Nebula Institute of Technology - Welisara</option>
                                <option value="Nebula Institute of Technology - Moratuwa" {{ old('location') === 'Nebula Institute of Technology - Moratuwa' ? 'selected' : '' }}>Nebula Institute of Technology - Moratuwa</option>
                                <option value="Nebula Institute of Technology - Peradeniya" {{ old('location') === 'Nebula Institute of Technology - Peradeniya' ? 'selected' : '' }}>Nebula Institute of Technology - Peradeniya</option>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group input-group-lg">
                                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" tabindex="-1" onclick="togglePasswordVisibility('password', 'togglePasswordIcon')">
                                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <div class="input-group input-group-lg">
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" tabindex="-1" onclick="togglePasswordVisibility('password_confirmation', 'toggleConfirmPasswordIcon')">
                                        <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="designation" class="form-label">Designation</label>
                                <select name="designation" id="designation" class="form-select form-select-lg">
                                    <option value="">Select designation</option>
                                    <option value="Admin" {{ old('designation') === 'Admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="Coordinator" {{ old('designation') === 'Coordinator' ? 'selected' : '' }}>Coordinator</option>
                                    <option value="Resource Owner" {{ old('designation') === 'Resource Owner' ? 'selected' : '' }}>Resource Owner</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="user_type" class="form-label">User Type</label>
                                <select name="user_type" id="user_type" class="form-select form-select-lg">
                                    <option value="">Select user type</option>
                                    <option value="Internal" {{ old('user_type') === 'Internal' ? 'selected' : '' }}>Internal</option>
                                    <option value="External" {{ old('user_type') === 'External' ? 'selected' : '' }}>External</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function togglePasswordVisibility(inputId, iconId) {
        const password = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (!password || !icon) {
            return;
        }

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>
@endpush
@endsection
