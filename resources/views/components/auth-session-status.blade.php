@props(['status'])

@if ($status)
    <div class="mb-4">
        <x-alert type="success" :timeout="0" class="!static !shadow-sm">
            {{ $status }}
        </x-alert>
    </div>
@endif
