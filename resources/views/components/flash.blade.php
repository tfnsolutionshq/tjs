@if (session('status') || session('success') || session('error') || session('warning') || session('info'))
    <div class="tjs-toast-host" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif
        @if (session('status'))
            @if (session('status') === 'verification-link-sent')
                <x-alert type="success" title="Email sent">
                    A new verification link has been sent to your email address.
                </x-alert>
            @else
                <x-alert type="success">{{ session('status') }}</x-alert>
            @endif
        @endif
        @if (session('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif
        @if (session('warning'))
            <x-alert type="warning">{{ session('warning') }}</x-alert>
        @endif
        @if (session('info'))
            <x-alert type="info">{{ session('info') }}</x-alert>
        @endif
    </div>
@endif
