@extends('layouts.app')

@section('title', 'Create Resources')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-management.css') }}?v=6">
@endpush

@section('content')
<div class="container-fluid mt-4 rm-page">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 rm-header">
        <div>
            <h1 class="rm-title mb-1">Create resources</h1>
            <p class="rm-subtitle mb-0">Set up a category, define its types, then add and link resources.</p>
        </div>
        <a href="{{ route('resources.index') }}" class="btn btn-outline-secondary">Back to inventory</a>
    </div>

    <div class="card border-0 rm-panel workflow-card">
        <div class="card-body p-3 p-md-4">
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
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editCategoryForm">
                    <div class="modal-body">
                        <label for="editCategoryName" class="form-label">Category name</label>
                        <input type="text" class="form-control" id="editCategoryName" required>
                        <div class="invalid-feedback">Enter a category name.</div>
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
<script src="{{ asset('js/resource-create.js') }}?v=2"></script>
@endpush
