@php
    $activeBreadcrumb = null;

    foreach (filament()->getNavigation() as $group) {
        foreach ($group->getItems() as $item) {
            if ($item->isActive()) {
                $activeBreadcrumb = [
                    $group->getLabel(),
                    $item->getLabel(),
                ];

                break 2;
            }
        }
    }
@endphp

@if ($activeBreadcrumb)
    <nav aria-label="Breadcrumb" class="fs-topbar-breadcrumb">
        @foreach ($activeBreadcrumb as $index => $label)
            @if ($index > 0)
                <x-filament::icon icon="heroicon-m-chevron-right" class="fs-topbar-breadcrumb-separator" />
            @endif

            <span @class(['fs-topbar-breadcrumb-current' => $loop->last])>{{ $label }}</span>
        @endforeach
    </nav>
@endif
