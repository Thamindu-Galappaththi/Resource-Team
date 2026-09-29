@props([
    'title',
    'description',
])

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h4 class="mb-2">{{ $title }}</h4>
                    <p class="text-muted mb-0">{{ $description }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
