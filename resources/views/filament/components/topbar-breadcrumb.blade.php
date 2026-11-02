@php
    $currentGroupLabel = null;
    $currentLabel = null;

    $findActiveNavigationLabel = function ($items) use (&$findActiveNavigationLabel) {
        foreach ($items as $item) {
            if (! $item->isActive()) {
                continue;
            }

            $childLabel = $findActiveNavigationLabel($item->getChildItems());

            return $childLabel ?? $item->getLabel();
        }

        return null;
    };

    foreach (filament()->getNavigation() as $group) {
        $activeLabel = $findActiveNavigationLabel($group->getItems());

        if ($activeLabel !== null) {
            $currentGroupLabel = $group->getLabel();
            $currentLabel = $activeLabel;
            break;
        }
    }

    $currentLabel ??= 'Dashboard';
@endphp

<nav aria-label="Breadcrumb" class="fs-topbar-breadcrumb">
    @if (filled($currentGroupLabel))
        <span class="fs-topbar-breadcrumb-parent">{{ $currentGroupLabel }}</span>
        <span class="fs-topbar-breadcrumb-separator" aria-hidden="true">›</span>
    @endif
    <span class="fs-topbar-breadcrumb-current">{{ $currentLabel }}</span>
</nav>
