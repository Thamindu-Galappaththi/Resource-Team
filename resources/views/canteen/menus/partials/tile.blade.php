<article class="mm-tile" data-menu-id="{{ $menu->id }}">
    @if($menu->image_path)
        <img src="{{ $menu->imageUrl() }}" alt="{{ $menu->name }}">
    @else
        <div class="mm-tile-placeholder"><i class="ti ti-photo" aria-hidden="true"></i></div>
    @endif
    <div class="mm-tile-body">
        <h3 class="text-break">{{ $menu->name }}</h3>
        <div class="mm-tile-actions">
            <a href="{{ route('canteen.menus.edit', $menu) }}" class="btn btn-sm btn-outline-secondary cp-icon-btn" title="Edit" aria-label="Edit {{ $menu->name }}"><i class="ti ti-pencil"></i></a>
            <button type="button" class="btn btn-sm btn-outline-danger cp-icon-btn" title="Delete" aria-label="Delete {{ $menu->name }}"
                data-cp-confirm
                data-cp-action="{{ route('canteen.menus.destroy', $menu) }}"
                data-cp-title="Delete meal menu?"
                data-cp-sub="{{ $menu->name }}"
                data-cp-copy="This menu will no longer be available when creating a canteen reservation."
                data-cp-submit="Delete menu"
                data-cp-icon="ti ti-trash"><i class="ti ti-trash"></i></button>
        </div>
    </div>
</article>
