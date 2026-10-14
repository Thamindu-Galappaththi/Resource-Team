@props([
    'id',
    'name' => null,
    'placeholder' => 'Select an option',
    'value' => '',
    'selectedLabel' => null,
    'options' => [],
    'disabled' => false,
    'required' => false,
])
@php
    $currentLabel = $selectedLabel;
    if ($currentLabel === null) {
        $match = collect($options)->first(fn ($option) => (string) $option['value'] === (string) $value);
        $currentLabel = $match['label'] ?? $placeholder;
    }
@endphp
<div {{ $attributes->except(['id', 'name', 'required', 'disabled'])->class(['dropdown', 'cu-select', 'is-disabled' => $disabled]) }} data-cu-select data-placeholder="{{ $placeholder }}">
    <input type="hidden" id="{{ $id }}" name="{{ $name ?? $id }}" value="{{ $value }}">
    <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-display="static"
        aria-expanded="false" aria-required="{{ $required ? 'true' : 'false' }}" @disabled($disabled)>
        <span class="js-select-label {{ $value === '' || $value === null ? 'text-muted' : '' }}">{{ $currentLabel }}</span>
    </button>
    <ul class="dropdown-menu cu-dropdown-menu">
        @foreach ($options as $option)
            <li>
                <button type="button" class="dropdown-item" data-value="{{ $option['value'] }}" data-label="{{ $option['label'] }}">
                    {{ $option['label'] }}
                </button>
            </li>
        @endforeach
    </ul>
</div>
