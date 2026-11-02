@php
    $currentPath = '/' . trim(request()->path(), '/');
    $currentPath = $currentPath === '/' ? '/' : rtrim($currentPath, '/');
    $currentLabel = null;
    $matchedPathLength = -1;

    foreach (\Filament\Facades\Filament::getNavigation() as $group) {
        foreach ($group->getItems() as $item) {
            $url = $item->getUrl();
            $path = parse_url($url, PHP_URL_PATH);

            if (! $path) {
                continue;
            }

            $path = rtrim($path, '/') ?: '/';
            $matches = $path === '/'
                ? $currentPath === '/'
                : ($currentPath === $path || str_starts_with($currentPath, $path . '/'));

            if ($matches && strlen($path) > $matchedPathLength) {
                $currentLabel = $item->getLabel();
                $matchedPathLength = strlen($path);
            }
        }
    }

    $currentLabel ??= 'Dashboard';
@endphp

<nav aria-label="Breadcrumb" class="fs-topbar-breadcrumb">
    <span class="fs-topbar-breadcrumb-current">{{ $currentLabel }}</span>
</nav>
