@php
    $logoutUrl = route('filament.admin.auth.logout');
@endphp

<div class="fs-sidebar-logout">
    <form method="POST" action="{{ $logoutUrl }}">
        @csrf
        <x-filament::button
            type="submit"
            color="danger"
            icon="heroicon-o-arrow-left-on-rectangle"
            class="w-full justify-start"
        >
            Logout
        </x-filament::button>
    </form>
</div>
