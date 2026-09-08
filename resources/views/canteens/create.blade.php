@extends('layouts.app')

@section('title', 'Create Canteen')

@section('content')
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-4">Create Canteen</h4>

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('canteens.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label">Canteen Name *</label>
                            <input id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="location_id" class="form-label">Location *</label>
                            <select name="location_id" id="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                                <option value="" disabled selected>-- Select Location --</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                            @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="contact_number" class="form-label">Contact Number</label>
                            <input id="contact_number" name="contact_number" value="{{ old('contact_number') }}" class="form-control @error('contact_number') is-invalid @enderror">
                            @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="opening_time" class="form-label">Opening Time *</label>
                                <input id="opening_time" name="opening_time" type="time" value="{{ old('opening_time') }}" class="form-control @error('opening_time') is-invalid @enderror" required>
                                @error('opening_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="closing_time" class="form-label">Closing Time *</label>
                                <input id="closing_time" name="closing_time" type="time" value="{{ old('closing_time') }}" class="form-control @error('closing_time') is-invalid @enderror" required>
                                @error('closing_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>

                        <button class="btn btn-primary">Create Canteen</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
