@extends('layouts.app')

@section('title', $tab === 'security' ? 'Security' : 'My Profile')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}?v={{ @filemtime(public_path('css/profile.css')) ?: time() }}">
@endpush

@section('content')
<div class="rs-profile">
    <header class="rs-profile-hero">
        <p class="rs-profile-kicker">Account</p>
        <h1>{{ $tab === 'security' ? 'Security' : 'My Profile' }}</h1>
        <p class="rs-profile-lead">
            @if($tab === 'security')
                Change your password here. Contact details stay on the Profile tab.
            @else
                Update the details used on reservations. Password changes are on the Security tab.
            @endif
        </p>
        <nav class="rs-tabs" aria-label="Account sections">
            <a
                class="rs-tab {{ $tab === 'profile' ? 'is-active' : '' }}"
                href="{{ route('user.profile') }}"
                @if($tab === 'profile') aria-current="page" @endif
            >Profile</a>
            <a
                class="rs-tab {{ $tab === 'security' ? 'is-active' : '' }}"
                href="{{ route('user.profile', ['tab' => 'security']) }}"
                @if($tab === 'security') aria-current="page" @endif
            >Security</a>
        </nav>
    </header>

    <div class="rs-profile-grid">
        <aside class="rs-profile-card">
            <div class="rs-avatar-block">
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}">
                <div class="rs-avatar-meta">
                    <strong>{{ $user->name }}</strong>
                    <span>{{ $user->role?->name ?? 'Unassigned role' }}</span>
                    <span class="rs-pill {{ $user->is_active ? 'is-ok' : 'is-off' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
            <ul class="rs-id-list">
                <li><span>NIC</span><strong>{{ $user->nic ?: '—' }}</strong></li>
                <li><span>Service ID</span><strong>{{ $user->service_id ?: '—' }}</strong></li>
                <li><span>SLT employee</span><strong>{{ $user->slt_employee ? 'Yes' : 'No' }}</strong></li>
                <li>
                    <span>Roles</span>
                    <strong>
                        {{ $user->roles->pluck('name')->push($user->role?->name)->filter()->unique()->implode(', ') ?: '—' }}
                    </strong>
                </li>
            </ul>
        </aside>

        <div class="rs-profile-stack">
            @if($tab === 'profile')
                <section class="rs-profile-card">
                    <h2>Profile details</h2>
                    <p class="hint">These details appear on reservations you create or request. NIC, service ID, and roles can only be changed by an administrator.</p>

                    @if(session('status') === 'profile-updated')
                        <div class="rs-alert rs-alert-ok" role="status">Your profile was updated.</div>
                    @endif

                    @if($errors->any() && ! $errors->updatePassword->any())
                        <div class="rs-alert rs-alert-err" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('user.profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full name</label>
                                <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required maxlength="50" autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required maxlength="50" autocomplete="email">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" required maxlength="20" autocomplete="tel">
                            </div>
                            <div class="col-md-6">
                                <label for="designation" class="form-label">Designation</label>
                                <input id="designation" name="designation" type="text" class="form-control @error('designation') is-invalid @enderror" value="{{ old('designation', $user->designation) }}" maxlength="100">
                            </div>
                            <div class="col-12">
                                <label for="location" class="form-label">Campus</label>
                                <select id="location" name="location" class="form-select @error('location') is-invalid @enderror" required>
                                    <option value="">Select location</option>
                                    @foreach($locations as $campus)
                                        <option value="{{ $campus }}" @selected(old('location', $user->location) === $campus)>{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="avatar" class="form-label">Profile photo</label>
                                <input id="avatar" name="avatar" type="file" class="form-control @error('avatar') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">JPG, PNG, or WebP. Max 2 MB.</div>
                                @if($user->user_profile)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="remove_avatar" id="remove_avatar" value="1">
                                        <label class="form-check-label" for="remove_avatar">Remove current photo</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="rs-profile-actions">
                            <button type="submit" class="rs-btn">Save profile</button>
                        </div>
                    </form>
                </section>
            @else
                <section class="rs-profile-card" id="password">
                    <h2>Change password</h2>
                    <p class="hint">Confirm your current password, then set a new one. Other signed-in devices will be signed out.</p>

                    @if(session('status') === 'password-updated')
                        <div class="rs-alert rs-alert-ok" role="status">Your password was changed.</div>
                    @endif

                    @if($errors->updatePassword->any())
                        <div class="rs-alert rs-alert-err" role="alert">{{ $errors->updatePassword->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('user.profile.password') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="current_password" class="form-label">Current password</label>
                                <div class="rs-password-wrap">
                                    <input id="current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" required>
                                    <button type="button" data-password-toggle="current_password" aria-label="Show password"><i class="ti ti-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">New password</label>
                                <div class="rs-password-wrap">
                                    <input id="password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" required minlength="8">
                                    <button type="button" data-password-toggle="password" aria-label="Show password"><i class="ti ti-eye"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm new password</label>
                                <div class="rs-password-wrap">
                                    <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required minlength="8">
                                    <button type="button" data-password-toggle="password_confirmation" aria-label="Show password"><i class="ti ti-eye"></i></button>
                                </div>
                            </div>
                        </div>

                        <ul class="rs-rules">
                            <li>At least 8 characters</li>
                            <li>Must match in both new password fields</li>
                            <li>This device stays signed in; other sessions are signed out</li>
                        </ul>

                        <div class="rs-profile-actions">
                            <button type="submit" class="rs-btn">Update password</button>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = document.getElementById(button.getAttribute('data-password-toggle'));
            if (!input) return;
            var hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            button.querySelector('i')?.classList.toggle('ti-eye');
            button.querySelector('i')?.classList.toggle('ti-eye-off');
        });
    });
</script>
@endpush
