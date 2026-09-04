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
                        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                    @endif

                    <form method="POST" action="{{ route('create.user.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="slt_employee" class="form-label">SLT Employee</label>
                            <select name="slt_employee" id="slt_employee" class="form-select form-select-lg" required>
                                <option value="">Select an option</option>
                                <option value="yes" @selected(old('slt_employee') === 'yes')>Yes</option>
                                <option value="no" @selected(old('slt_employee') === 'no')>No</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Employee ID <span id="employeeIdRequired" class="text-danger d-none">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="service_id" id="service_id" class="form-control" value="{{ old('service_id') }}" placeholder="Enter employee ID">
                                <button id="lookupEmployee" class="btn btn-outline-primary d-none" type="button">Find employee</button>
                            </div>
                            <small id="lookupMessage" class="form-text"></small>
                        </div>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" class="form-control form-control-lg" value="{{ old('name') }}" placeholder="Enter full name" required>
                        </div>
                        <div class="mb-3">
                            <label for="nic" class="form-label">NIC</label>
                            <input type="text" name="nic" id="nic" class="form-control form-control-lg" value="{{ old('nic') }}" placeholder="Enter NIC" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">E-mail</label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg" value="{{ old('email') }}" placeholder="Enter e-mail address" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone No.</label>
                            <input type="tel" name="phone" id="phone" class="form-control form-control-lg" value="{{ old('phone') }}" placeholder="Enter phone number" required>
                        </div>
                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation" class="form-control form-control-lg" value="{{ old('designation') }}" placeholder="Enter designation">
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="user_role" class="form-label">Role</label>
                                <select name="user_role" id="user_role" class="form-select form-select-lg" required>
                                    <option value="">Select role</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->slug }}" @selected(old('user_role') === $role->slug)>{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location</label>
                                <select name="location" id="location" class="form-select form-select-lg" required>
                                    <option value="">Select location</option>
                                    <option value="Nebula Institute of Technology - Welisara" @selected(old('location') === 'Nebula Institute of Technology - Welisara')>Nebula Institute of Technology - Welisara</option>
                                    <option value="Nebula Institute of Technology - Moratuwa" @selected(old('location') === 'Nebula Institute of Technology - Moratuwa')>Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Nebula Institute of Technology - Peradeniya" @selected(old('location') === 'Nebula Institute of Technology - Peradeniya')>Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-grid"><button type="submit" class="btn btn-primary btn-lg">Create User</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const sltEmployee = document.getElementById('slt_employee');
    const employeeId = document.getElementById('service_id');
    const lookupButton = document.getElementById('lookupEmployee');
    const lookupMessage = document.getElementById('lookupMessage');
    const employeeFields = ['name', 'nic', 'email', 'phone'].map((id) => document.getElementById(id));

    function setSltEmployeeMode() {
        const isSltEmployee = sltEmployee.value === 'yes';
        employeeId.required = isSltEmployee;
        document.getElementById('employeeIdRequired').classList.toggle('d-none', !isSltEmployee);
        lookupButton.classList.toggle('d-none', !isSltEmployee);
        employeeFields.forEach((field) => field.readOnly = isSltEmployee);
        if (!isSltEmployee) {
            lookupMessage.textContent = '';
            lookupMessage.className = 'form-text';
        }
    }

    async function lookupEmployee() {
        if (!employeeId.value.trim()) {
            lookupMessage.textContent = 'Enter an Employee ID first.';
            lookupMessage.className = 'form-text text-danger';
            return;
        }
        lookupButton.disabled = true;
        lookupMessage.textContent = 'Looking up employee details...';
        lookupMessage.className = 'form-text text-muted';
        try {
            const response = await fetch(`{{ route('slt.employee.lookup') }}?employee_id=${encodeURIComponent(employeeId.value.trim())}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Employee lookup failed.');
            employeeFields.forEach((field) => field.value = data[field.id] || '');
            lookupMessage.textContent = 'Employee details loaded.';
            lookupMessage.className = 'form-text text-success';
        } catch (error) {
            lookupMessage.textContent = error.message;
            lookupMessage.className = 'form-text text-danger';
        } finally {
            lookupButton.disabled = false;
        }
    }

    sltEmployee.addEventListener('change', setSltEmployeeMode);
    lookupButton.addEventListener('click', lookupEmployee);
    setSltEmployeeMode();
</script>
@endpush
@endsection
