@props([
    'id',
    'name' => null,
    'placeholder' => 'Select an option',
    'value' => '',
    'values' => [],
    'selectedLabel' => null,
    'options' => [],
    'disabled' => false,
    'required' => false,
    'multiple' => false,
    'arrayName' => null,
])
@php
    $selectedValues = collect($values ?: [])
        ->when($value !== '' && $value !== null, fn ($items) => $items->prepend($value))
        ->map(fn ($item) => (string) $item)
        ->filter(fn ($item) => $item !== '')
        ->unique()
        ->values();
    $currentLabel = $selectedLabel;
    if ($currentLabel === null) {
        $labels = $selectedValues
            ->map(fn ($item) => collect($options)->first(fn ($option) => (string) $option['value'] === $item)['label'] ?? null)
            ->filter()
            ->values();
        $currentLabel = $labels->isEmpty()
            ? $placeholder
            : ($labels->count() <= 2 ? $labels->implode(', ') : $labels->first().' +'.($labels->count() - 1).' more');
    }
    $primaryValue = $multiple ? ($selectedValues->first() ?? '') : $value;
@endphp
<div {{ $attributes->except(['id', 'name', 'required', 'disabled', 'multiple', 'arrayName'])->class(['dropdown', 'cu-select', 'is-disabled' => $disabled]) }} data-cu-select @if($multiple) data-cu-multiple data-cu-array="{{ $arrayName ?? $id.'s[]' }}" @endif data-placeholder="{{ $placeholder }}">
    <input type="hidden" id="{{ $id }}" name="{{ $name ?? $id }}" value="{{ $primaryValue }}">
    <button class="btn cu-dropdown-toggle dropdown-toggle w-100" type="button" data-bs-toggle="dropdown" data-bs-display="static"
        @if($multiple) data-bs-auto-close="outside" @endif
        aria-expanded="false" aria-required="{{ $required ? 'true' : 'false' }}" @disabled($disabled)>
        <span class="js-select-label {{ $primaryValue === '' || $primaryValue === null ? 'text-muted' : '' }}">{{ $currentLabel }}</span>
    </button>
    <ul class="dropdown-menu cu-dropdown-menu">
        @foreach ($options as $option)
            <li>
                @if($multiple)
                    <button type="button" class="dropdown-item cu-check-item{{ $selectedValues->contains((string) $option['value']) ? ' is-selected' : '' }}" data-value="{{ $option['value'] }}" data-label="{{ $option['label'] }}">
                        <input type="checkbox" class="form-check-input" tabindex="-1" @checked($selectedValues->contains((string) $option['value']))>
                        <span>{{ $option['label'] }}</span>
                    </button>
                @else
                    <button type="button" class="dropdown-item" data-value="{{ $option['value'] }}" data-label="{{ $option['label'] }}">
                        {{ $option['label'] }}
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
</div>
