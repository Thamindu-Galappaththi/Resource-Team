@extends('layouts.app')

@section('title', 'Meal plans')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
<style>
    .mm-layout{display:grid;grid-template-columns:minmax(300px,.85fr) minmax(0,1.5fr);gap:1.25rem;align-items:start}
    .mm-card{background:#fff;border:1px solid #e8edf3;border-radius:16px;padding:1.25rem;box-shadow:0 4px 18px rgba(31,45,61,.04)}
    .mm-card h2{font-size:1.05rem;font-weight:700;margin-bottom:.25rem}.mm-muted{color:#718096;font-size:.875rem}
    .mm-fields{display:grid;grid-template-columns:1fr 1fr;gap:.85rem}.mm-fields .wide{grid-column:1/-1}
    .mm-dishes{display:grid;grid-template-columns:1fr 1fr;gap:.75rem}.mm-dish{border:1px solid #e8edf3;border-radius:12px;padding:.8rem}
    .mm-dish label{font-size:.75rem;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:.04em}
    .mm-main{border-color:#f4d89c;background:#fffbf0}.mm-menu{padding:1rem 0;border-bottom:1px solid #edf0f4}.mm-menu:last-child{border-bottom:0;padding-bottom:0}
    .mm-menu-head{display:flex;justify-content:space-between;gap:1rem;align-items:center}.mm-chip{font-size:.75rem;border-radius:999px;background:#eef5ff;color:#315b91;padding:.3rem .65rem;white-space:nowrap}
    .mm-menu{display:grid;grid-template-columns:92px minmax(0,1fr);gap:1rem;padding:1rem 0;border-bottom:1px solid #e5eaf0}.mm-menu:last-child{border-bottom:0;padding-bottom:0}
    .mm-menu-image{width:92px;height:92px;object-fit:cover;border-radius:10px;background:#f3f5f8}.mm-menu-content{min-width:0}.mm-dishes{display:flex;gap:.5rem;overflow-x:auto;padding-bottom:.25rem}.mm-dish{flex:0 0 auto;min-width:110px;max-width:180px;border:1px solid #e8edf3;border-radius:9px;padding:.55rem .7rem}.mm-main{border-color:#f4d89c;background:#fffbf0}
    .mm-dish label{font-size:.68rem;font-weight:700;color:#718096;text-transform:uppercase;letter-spacing:.04em}.mm-menu-head{display:flex;justify-content:space-between;gap:.75rem;align-items:center}.mm-chip{font-size:.75rem;border-radius:999px;background:#eef5ff;color:#315b91;padding:.3rem .65rem;white-space:nowrap}
    @media(max-width:900px){.mm-layout{grid-template-columns:1fr}}@media(max-width:520px){.mm-fields{grid-template-columns:1fr}.mm-fields .wide{grid-column:auto}.mm-menu{grid-template-columns:68px minmax(0,1fr);gap:.65rem}.mm-menu-image{width:68px;height:68px}.mm-dish{min-width:96px}.mm-menu-head{align-items:center}}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Meal plans</h1>
            <p class="cd-subtitle">Plan and keep track of the dishes served each day.</p>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">Please check the highlighted menu details and try again.</div>@endif

    <div class="mm-layout">
        <section class="mm-card">
            <h2>Create a meal menu</h2>
            <p class="mm-muted mb-4">Add one main dish and exactly four accompanying dishes.</p>
            <form method="POST" action="{{ route('canteen.menus.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mm-fields mb-3">
                    <div class="wide"><label for="name" class="form-label">Menu name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Daily lunch menu" maxlength="120" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="wide"><label for="service_date" class="form-label">Service date</label><input id="service_date" type="date" name="service_date" class="form-control @error('service_date') is-invalid @enderror" value="{{ old('service_date', now()->toDateString()) }}" required>@error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                </div>
                <div class="mm-dishes mb-3">
                    <div class="mm-dish mm-main"><label for="main_dish">Main dish</label><input id="main_dish" name="main_dish" class="form-control mt-1 @error('main_dish') is-invalid @enderror" value="{{ old('main_dish') }}" placeholder="Main dish" maxlength="120" required>@error('main_dish')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    @foreach(['dish_one' => 'Dish 1', 'dish_two' => 'Dish 2', 'dish_three' => 'Dish 3', 'dish_four' => 'Dish 4'] as $field => $label)
                        <div class="mm-dish"><label for="{{ $field }}">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" class="form-control mt-1 @error($field) is-invalid @enderror" value="{{ old($field) }}" placeholder="Enter dish" maxlength="120" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    @endforeach
                </div>
                <div class="mb-3"><label for="image" class="form-label">Meal image</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror" required><div class="form-text">JPG, PNG, or WebP, up to 5 MB.</div>@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <button type="submit" class="cd-btn cd-btn--solid w-100 justify-content-center"><i class="ti ti-plus" aria-hidden="true"></i>Save meal menu</button>
            </form>
        </section>

        <section class="mm-card">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div><h2>Saved menus</h2><p class="mm-muted mb-0">Your latest menus appear first.</p></div><span class="mm-chip">{{ $menus->count() }} {{ \Illuminate\Support\Str::plural('menu', $menus->count()) }}</span></div>
            @forelse($menus as $menu)
                <article class="mm-menu">
                    @if($menu->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($menu->image_path))<img class="mm-menu-image" src="{{ url('storage/'.$menu->image_path) }}" alt="Meal for {{ $menu->name }}">@else<div class="mm-menu-image d-flex align-items-center justify-content-center text-muted"><i class="ti ti-photo fs-3" aria-hidden="true"></i></div>@endif
                    <div class="mm-menu-content">
                        <div class="mm-menu-head mb-2"><div class="text-truncate"><h3 class="h6 fw-bold mb-1 text-truncate">{{ $menu->name }}</h3><div class="mm-muted"><i class="ti ti-calendar me-1" aria-hidden="true"></i>{{ $menu->service_date->format('d M Y') }}</div></div><div class="d-flex gap-1 flex-shrink-0"><a href="{{ route('canteen.menus.edit', $menu) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-pencil me-1"></i>Edit</a><form method="POST" action="{{ route('canteen.menus.destroy', $menu) }}" onsubmit="return confirm('Remove this saved menu? This cannot be undone.')">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $menu->name }}"><i class="ti ti-trash"></i></button></form></div></div>
                        <div class="mm-dishes"><div class="mm-dish mm-main"><label>Main dish</label><div class="fw-semibold mt-1 text-truncate">{{ $menu->main_dish }}</div></div>@foreach([$menu->dish_one, $menu->dish_two, $menu->dish_three, $menu->dish_four] as $index => $dish)<div class="mm-dish"><label>Dish {{ $index + 1 }}</label><div class="fw-semibold mt-1 text-truncate">{{ $dish }}</div></div>@endforeach</div>
                    </div>
                </article>
            @empty
                <div class="text-center py-5"><i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i><h3 class="h6 mt-3">No menus yet</h3><p class="mm-muted mb-0">Create the first menu using the form.</p></div>
            @endforelse
        </section>
    </div>
</div>
@endsection
