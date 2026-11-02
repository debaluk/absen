@php
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

    $currentLabel = null;

    foreach (filament()->getNavigation() as $group) {
        $currentLabel = $findActiveNavigationLabel($group->getItems());

        if ($currentLabel !== null) {
            break;
        }
    }

    $currentLabel ??= 'Dashboard';
@endphp

<nav aria-label="Breadcrumb" class="fs-topbar-breadcrumb">
    <span class="fs-topbar-breadcrumb-current">{{ $currentLabel }}</span>
</nav>
