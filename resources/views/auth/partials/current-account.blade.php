@php
    $activeUser = $activeUser ?? auth()->user();
@endphp

@if ($activeUser)
    {{-- // Identifies the active Laravel session before the user attempts another role login. --}}
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
        <div class="flex items-center gap-3">
            <x-user-avatar :user="$activeUser" class="grid h-10 w-10 place-items-center rounded-full bg-amber-100 font-bold text-amber-800" />
            <div class="min-w-0">
                <p class="font-bold">Currently signed in</p>
                <p class="truncate">{{ $activeUser->name }} · {{ \App\Support\AccountPortal::roleLabelFor($activeUser) }} · {{ $activeUser->email }}</p>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a href="{{ \App\Support\AccountPortal::urlFor($activeUser) }}" class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700">
                {{ \App\Support\AccountPortal::ctaLabelFor($activeUser) }}
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-amber-200 bg-white px-4 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100">
                    Sign out to switch account
                </button>
            </form>
        </div>
    </div>
@endif
