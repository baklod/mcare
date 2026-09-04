@extends('trainer.layouts.app', ['title' => 'Search | MCARE Trainer'])

@section('content')
<div class="w-full space-y-6">
    <header class="border-b border-stone-200 pb-5">
        <p class="dashboard-section-kicker">Trainer search</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">
            @if ($query === '')
                Search the trainer portal
            @else
                Results for “{{ $query }}”
            @endif
        </h1>
        <p class="mt-2 text-sm text-stone-600">
            @if ($query === '')
                Find pages, people, modules, quizzes, classes, and stream posts from this trainer workspace.
            @elseif (mb_strlen($query) < 2)
                Type at least two characters to search.
            @elseif ($resultCount === 0)
                No matching pages or records were found in your assigned class.
            @else
                {{ $resultCount }} {{ \Illuminate\Support\Str::plural('match', $resultCount) }} across your trainer pages and class data.
            @endif
        </p>
    </header>

    @forelse ($groups as $group)
        <section class="dashboard-table-wrap" aria-labelledby="search-{{ $group['key'] }}">
            <div class="border-b border-slate-200 px-4 py-3">
                <h2 id="search-{{ $group['key'] }}" class="text-sm font-bold uppercase tracking-[0.12em] text-slate-500">{{ $group['label'] }}</h2>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($group['results'] as $result)
                    <li>
                        <a href="{{ $result['href'] }}" class="flex flex-col gap-0.5 px-4 py-3 hover:bg-violet-50">
                            <span class="font-semibold text-slate-950">{{ $result['title'] }}</span>
                            @if ($result['subtitle'] !== '')
                                <span class="text-sm text-slate-500">{{ $result['subtitle'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @empty
        @if ($query !== '' && mb_strlen($query) >= 2)
            <div class="dashboard-panel">
                <p class="text-sm text-slate-600">Try a page name such as People or Classwork, a trainee name, or a module code.</p>
            </div>
        @endif
    @endforelse
</div>
@endsection
