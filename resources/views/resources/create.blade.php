@extends('layouts.app')

@section('title', 'Create Resources')

@push('styles')
<<<<<<< HEAD
<style>
    .resource-create-page { --rc-ink:#152238; --rc-muted:#65738a; --rc-line:#dfe5ee; --rc-blue:#315fe8; color:var(--rc-ink); }
    .resource-create-page .page-heading { font-weight:700; letter-spacing:-.035em; color:var(--rc-ink); }
    .resource-create-page .page-subtitle, .resource-create-page .step-hint { color:var(--rc-muted); }
    .resource-create-page .workflow-card { border:1px solid #dce3ed; border-radius:16px; background:#fff; box-shadow:0 10px 30px rgba(19,35,58,.09); overflow:hidden; }
    .resource-create-page .workflow-card > .card-body { background:linear-gradient(180deg,#fff 0%,#fbfcfe 100%); }
    .resource-create-page .workflow-tabs { display:flex; flex-wrap:nowrap; gap:.45rem; padding:.6rem 1rem; border:1px solid #d8e0eb; border-radius:17px; background:#edf1f7; overflow-x:auto; }
    .resource-create-page .workflow-tabs .nav-link { display:flex; align-items:center; gap:.65rem; border:1px solid transparent; border-radius:13px; padding:1rem 1.15rem; color:#4e5c71; white-space:nowrap; transition:background .18s ease,color .18s ease,box-shadow .18s ease; }
    .resource-create-page .workflow-tabs .nav-link:hover { background:#fff; color:var(--rc-ink); }
    .resource-create-page .workflow-tabs .nav-link.active { background:#fff; border-color:#e4e9f1; color:#204bc9; box-shadow:0 4px 12px rgba(19,35,58,.1); font-weight:650; }
    .resource-create-page .workflow-content { padding:clamp(1rem,3vw,2rem); }
    .resource-create-page .step-panel { max-width:1000px; margin:0 auto; padding:clamp(1.25rem,3vw,2.25rem); background:#fff; border:1px solid #dfe5ee; border-radius:16px; box-shadow:0 9px 26px rgba(19,35,58,.075); }
    .resource-create-page .step-title { font-size:1.45rem; font-weight:650; letter-spacing:-.025em; }
    .resource-create-page .form-label { color:#202e43; font-weight:600; margin-bottom:.5rem; }
    .resource-create-page .form-control, .resource-create-page .form-select { min-height:46px; border-color:#cfd8e5; border-radius:9px; background-color:#fff; color:#18263a; box-shadow:0 1px 2px rgba(19,35,58,.035); }
    .resource-create-page .form-control::placeholder { color:#8591a3; }
    .resource-create-page .form-control:focus, .resource-create-page .form-select:focus { border-color:#5578e8; box-shadow:0 0 0 .2rem rgba(49,95,232,.15); }
    .resource-create-page .btn-primary { --bs-btn-bg:#315fe8; --bs-btn-border-color:#315fe8; --bs-btn-hover-bg:#244ecb; --bs-btn-hover-border-color:#244ecb; --bs-btn-active-bg:#1e43b1; --bs-btn-active-border-color:#1e43b1; border-radius:9px; padding:.65rem 1.15rem; font-weight:600; box-shadow:0 4px 10px rgba(49,95,232,.2); }
    .resource-create-page .feature-heading { border-top:1px solid var(--rc-line); padding-top:1.25rem; }
    .resource-create-page #featuresContainer .row { align-items:center; row-gap:.75rem; margin:0 0 .75rem; padding:1rem; border:1px solid #e0e6ef; border-radius:12px; background:#f7f9fc; }
    .resource-create-page .existing-card { border:1px solid #dce3ed; border-radius:16px; background:#fff; box-shadow:0 9px 26px rgba(19,35,58,.08); overflow:hidden; }
    .resource-create-page .existing-card .card-header { background:#edf1f7; color:var(--rc-ink); }
    @media (max-width:575.98px) { .resource-create-page .workflow-tabs { margin:0 -1rem; border-radius:0; } .resource-create-page .workflow-content { padding:1rem 0 0; } .resource-create-page .step-panel { padding:1.15rem; border-radius:12px; } }
</style>
@endpush

<div class="container-fluid mt-4 resource-create-page">
    <div class="row">
        <div class="col-12">
            <div class="resource-page-banner">
                <h1 class="page-heading">Create resources</h1>
                <p class="page-subtitle">Set up a category, define its types, then add and link your resources.</p>
            </div>
            <div class="card workflow-card">
                <div class="card-body">
                    {{-- ===================== NAV TABS ===================== --}}
=======
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=21">
@endpush

@section('content')
<div class="container-fluid mt-4 rm-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 rm-header">
        <div>
            <h1 class="rm-title mb-1">Create resources</h1>
            <p class="rm-subtitle mb-0">Set up a category, define its types, then add and link resources.</p>
        </div>
        <a href="{{ route('resources.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>Back to inventory</a>
                    </div>

    <div class="card border-0 rm-panel workflow-card">
        <div class="card-body p-3 p-md-4">
>>>>>>> 83fa4dc2490e6d9583ee5be9e7771a3b3088792c
                    <ul class="nav nav-tabs workflow-tabs" id="resourceTabs" role="tablist" aria-label="Resource creation steps">
                        <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="category-tab" data-bs-toggle="tab" data-bs-target="#category-pane" type="button" role="tab">
                        <span class="rm-step-num">1</span><span>Category</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                    <button class="nav-link" id="type-tab" data-bs-toggle="tab" data-bs-target="#type-pane" type="button" role="tab">
                        <span class="rm-step-num">2</span><span>Resource type</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resource-tab" data-bs-toggle="tab" data-bs-target="#resource-pane" type="button" role="tab">
                        <span class="rm-step-num">3</span><span>Resource</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                    <button class="nav-link" id="linked-tab" data-bs-toggle="tab" data-bs-target="#linked-pane" type="button" role="tab">
                        <span class="rm-step-num">4</span><span>Link resources</span>
                            </button>
                        </li>
                    </ul>

            <div class="tab-content pt-4" id="resourceTabsContent">
                        <div class="tab-pane fade show active" id="category-pane" role="tabpanel">
                            <section class="step-panel">
                        <div class="mb-4">
                            <h2 class="step-title mb-1">Create a resource category</h2>
                            <p class="step-hint mb-0">Group similar items together and add any details you want to track.</p>
                        </div>
                        <form id="categoryForm" novalidate>
                                @csrf
                                <div class="mb-3">
                                    <label for="categoryName" class="form-label">Category name</label>
                                    <input type="text" class="form-control" id="categoryName" name="category_name" required>
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 feature-heading flex-wrap gap-2">
                                <div>
                                    <h3 class="h6 fw-semibold mb-1">Additional features <span class="text-muted fw-normal">(optional)</span></h3>
                                    <p class="step-hint small mb-0">Add fields such as brand, capacity, or purchase date.</p>
                                </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFeatureBtn">
                                    <i class="ti ti-plus me-1"></i> Add feature
                                    </button>
                                </div>
                            <div id="featuresContainer"></div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Create category</button>
                                </div>
                                </form>
                            </section>
                        </div>

                        <div class="tab-pane fade" id="type-pane" role="tabpanel">
                    <section class="step-panel">
                        <div class="mb-4">
                            <h2 class="step-title mb-1">Add a resource type</h2>
                            <p class="step-hint mb-0">Choose a category and give this type a clear name.</p>
                        </div>
                        <form id="typeForm" novalidate>
                                @csrf
                                <div class="mb-3">
                                <label class="form-label">Category</label>
                                <x-rm-select id="typeCategorySelect" name="category_id" class="category-select" required
                                    placeholder="Select a category"
                                    :options="[['value' => '', 'label' => 'Select a category']]" />
                                <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                    <label for="resourceTypeName" class="form-label">Type name</label>
                                    <input type="text" class="form-control" id="resourceTypeName" name="resource_type" required>
                                <div class="invalid-feedback"></div>
                                </div>
                                <div class="mb-3">
                                <label class="form-label">Additional features</label>
                                    <div id="typeAdditionalFeatures" class="border rounded p-3 bg-light text-muted small" aria-live="polite">
                                        Select a category above to view its additional features.
                                    </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Create resource type</button>
                        </form>
                    </section>
                                </div>

                <div class="tab-pane fade" id="resource-pane" role="tabpanel">
                    <section class="step-panel">
                        <div class="mb-4">
                            <h2 class="step-title mb-1">Add a resource</h2>
                            <p class="step-hint mb-0">Enter the key details so it can be found and managed later.</p>
                        </div>
                        <form id="resourceForm" novalidate>
                                @csrf
                                @include('resources.partials.resource-fields')
                            <button type="submit" class="btn btn-primary" id="saveResourceButton">Create resource</button>
                        </form>
                    </section>
                </div>

                <div class="tab-pane fade" id="linked-pane" role="tabpanel">
                    <section class="step-panel">
                        <div class="mb-4">
                            <h2 class="step-title mb-1">Link resources</h2>
                            <p class="step-hint mb-0">Connect related items so their relationship is easy to find.</p>
                        </div>
                        <form id="linkedForm" novalidate>
                                @csrf
                                <div class="mb-3">
                                <label class="form-label">Main resource</label>
                                <x-rm-select id="primaryResourceSelect" name="resource_id" class="resource-select dropup" required
                                    placeholder="Select a resource"
                                    :options="[['value' => '', 'label' => 'Select a resource']]" />
                                </div>
                                <div class="mb-3">
                                <label class="form-label">Related resource</label>
                                <x-rm-select id="linkedResourceSelect" name="linked_resource_id" class="resource-select dropup" required
                                    placeholder="Select a resource to link"
                                    :options="[['value' => '', 'label' => 'Select a resource to link']]" />
                                </div>
                            <button type="submit" class="btn btn-primary">Link resources</button>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 rm-panel mt-4" id="existingCategoriesCard">
        <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
            <h2 class="h6 mb-0"><i class="ti ti-list me-2"></i>Existing categories</h2>
        </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Category</th><th>Features</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody id="existingCategoriesBody">
                        <tr><td colspan="3" class="text-muted">Loading categories…</td></tr>
                    </tbody>
                        </table>
                    </div>
        </div>
    </div>

    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                <div class="modal-header">
                    <span class="rm-modal-icon" aria-hidden="true"><i class="ti ti-pencil"></i></span>
                    <div>
                        <h5 class="modal-title" id="editCategoryModalLabel">Edit category</h5>
                        <p class="rm-modal-sub mb-0">Update the category name and features.</p>
                    </div>
                    <button type="button" class="rm-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <form id="editCategoryForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editCategoryName" class="form-label">Category name</label>
                            <input type="text" class="form-control" id="editCategoryName" required>
                            <div class="invalid-feedback">Enter a category name.</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3 feature-heading flex-wrap gap-2">
                            <div>
                                <h3 class="h6 fw-semibold mb-1">Additional features <span class="text-muted fw-normal">(optional)</span></h3>
                                <p class="step-hint small mb-0">Edit existing fields or add new ones such as brand or capacity.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="editAddFeatureBtn">
                                <i class="ti ti-plus me-1"></i> Add feature
                            </button>
                        </div>
                        <p class="text-muted small mb-2" id="editFeaturesEmpty">No features yet. Add a feature to track extra details.</p>
                        <div id="editFeaturesContainer"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="editCategorySave">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    window.RCCreate = {
        categoriesUrl: @json(route('resource-categories.index')),
        typesUrl: @json(route('resource-types.index')),
        listUrl: @json(route('resources.list')),
        locationsUrl: @json(route('locations.index')),
        resourceUrl: @json(url('/resources')),
        linksUrl: @json(route('resource-links.store')),
        indexUrl: @json(route('resources.index')),
    };
</script>
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/resource-create.js') }}?v=7"></script>
@endpush
