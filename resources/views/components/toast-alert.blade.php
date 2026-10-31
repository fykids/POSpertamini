@php
    $alert = collect(['success', 'error', 'warning', 'info'])
        ->map(fn (string $type) => ['type' => $type, 'message' => session($type)])
        ->first(fn (array $item) => filled($item['message']));

    $styles = [
        'success' => ['container' => 'border-emerald-200 bg-emerald-50 text-emerald-900', 'icon' => 'text-emerald-600'],
        'error' => ['container' => 'border-rose-200 bg-rose-50 text-rose-900', 'icon' => 'text-rose-600'],
        'warning' => ['container' => 'border-amber-200 bg-amber-50 text-amber-900', 'icon' => 'text-amber-600'],
        'info' => ['container' => 'border-sky-200 bg-sky-50 text-sky-900', 'icon' => 'text-sky-600'],
    ];
@endphp

@if ($alert)
    <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-2 opacity-0"
        class="fixed right-5 top-5 z-[100] flex w-[calc(100%-2.5rem)] max-w-md items-start gap-3 rounded-xl border px-4 py-3 shadow-lg {{ $styles[$alert['type']]['container'] }}"
        role="alert" aria-live="polite">
        <span class="mt-0.5 shrink-0 {{ $styles[$alert['type']]['icon'] }}" aria-hidden="true">
            @if ($alert['type'] === 'success')
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16Zm3.707-9.293a1 1 0 00-1.414-1.414L9 11.586 7.707 10.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4Z" clip-rule="evenodd"/></svg>
            @elseif ($alert['type'] === 'error')
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0ZM10 5a1 1 0 011 1v3a1 1 0 11-2 0V6a1 1 0 011-1Zm0 8a1 1 0 100-2 1 1 0 000 2Z" clip-rule="evenodd"/></svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10A8 8 0 112 10a8 8 0 0116 0ZM10 6a1 1 0 011 1v3a1 1 0 11-2 0V7a1 1 0 011-1Zm0 8a1 1 0 100-2 1 1 0 000 2Z" clip-rule="evenodd"/></svg>
            @endif
        </span>
        <p class="flex-1 text-sm font-medium leading-6">{{ $alert['message'] }}</p>
        <button type="button" @click="show = false" class="rounded-md p-1 opacity-60 transition hover:bg-black/5 hover:opacity-100" aria-label="Tutup notifikasi">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg>
        </button>
    </div>
@endif
