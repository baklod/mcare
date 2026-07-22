@props([
    'month',
    'sessions' => null,
    'monthRoute' => null,
    'selectedDate' => null,
    'eyebrow' => 'Schedule',
    'heading' => 'Training calendar',
    'description' => 'Select a date to review every scheduled session.',
    'emptyMessage' => 'No sessions are scheduled for this date.',
    'showBatch' => true,
    'editable' => false,
])

@php
    $sessions = collect($sessions ?? [])->values();
    $eventsByDate = $sessions->groupBy('date_key');
    $monthRoute = $monthRoute ?: url()->current();
    $gridStart = $month->copy()->startOfMonth()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY);
    $calendarDays = collect(range(0, 41))->map(fn ($offset) => $gridStart->copy()->addDays($offset));
    $dayKeys = $calendarDays->map->toDateString();
    $todayKey = now()->toDateString();
    $initialDate = $selectedDate && $dayKeys->contains($selectedDate)
        ? $selectedDate
        : ($month->isSameMonth(now())
            ? $todayKey
            : ($sessions->first()['date_key'] ?? $month->toDateString()));
    $calendarId = 'training-calendar-'.substr(md5($heading.$monthRoute.$month->format('Y-m')), 0, 10);
    $baseQuery = collect(request()->query())->except(['page', 'date'])->all();
    $calendarUrl = function ($targetMonth, ?string $date = null) use ($monthRoute, $baseQuery) {
        $query = array_merge($baseQuery, ['month' => $targetMonth->format('Y-m')]);
        if ($date) $query['date'] = $date;

        return $monthRoute.'?'.http_build_query($query);
    };
@endphp

<section id="{{ $calendarId }}" class="training-calendar" data-training-calendar data-initial-date="{{ $initialDate }}">
    <header class="training-calendar-toolbar">
        <div class="min-w-0">
            <p class="training-calendar-eyebrow">{{ $eyebrow }}</p>
            <h2 class="training-calendar-heading">{{ $heading }}</h2>
            <p class="training-calendar-description">{{ $description }}</p>
        </div>

        <div class="training-calendar-controls" aria-label="Calendar navigation">
            <a href="{{ $calendarUrl(now()->startOfMonth(), $todayKey) }}" class="training-calendar-today">Today</a>
            <a href="{{ $calendarUrl($month->copy()->subMonthNoOverflow()) }}" class="training-calendar-arrow" aria-label="Previous month" title="Previous month">
                <x-dashboard-icon name="chevron-left" />
            </a>
            <a href="{{ $calendarUrl($month->copy()->addMonthNoOverflow()) }}" class="training-calendar-arrow" aria-label="Next month" title="Next month">
                <x-dashboard-icon name="chevron-right" />
            </a>
            <form method="GET" action="{{ $monthRoute }}" class="training-calendar-month-form">
                @foreach ($baseQuery as $key => $value)
                    @if ($key !== 'month' && is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="{{ $calendarId }}-month" class="sr-only">Choose month</label>
                <input id="{{ $calendarId }}-month" type="month" name="month" value="{{ $month->format('Y-m') }}">
                <button type="submit">Go</button>
            </form>
        </div>
    </header>

    <div class="training-calendar-layout">
        <div class="training-calendar-board">
            <div class="training-calendar-weekdays" aria-hidden="true">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $weekday)
                    <span>{{ $weekday }}</span>
                @endforeach
            </div>

            <div class="training-calendar-grid" role="grid" aria-label="{{ $month->format('F Y') }} training schedule">
                @foreach ($calendarDays->chunk(7) as $week)
                    <div class="training-calendar-week" role="row">
                @foreach ($week as $day)
                    @php
                        $dateKey = $day->toDateString();
                        $dayEvents = $eventsByDate->get($dateKey, collect());
                        $isCurrentMonth = $day->month === $month->month;
                        $isToday = $dateKey === $todayKey;
                        $isSelected = $dateKey === $initialDate;
                    @endphp
                    <button
                        type="button"
                        class="training-calendar-day {{ $isCurrentMonth ? '' : 'is-outside-month' }} {{ $isToday ? 'is-today' : '' }} {{ $isSelected ? 'is-selected' : '' }}"
                        data-calendar-day
                        data-calendar-date="{{ $dateKey }}"
                        @if(! $isCurrentMonth) data-calendar-month-url="{{ $calendarUrl($day->copy()->startOfMonth(), $dateKey) }}" @endif
                        aria-label="{{ $day->format('l, F j, Y') }}; {{ $dayEvents->count() }} {{ \Illuminate\Support\Str::plural('session', $dayEvents->count()) }}"
                        aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                        @if($isToday) aria-current="date" @endif
                        role="gridcell"
                    >
                        <span class="training-calendar-date-number">{{ $day->day }}</span>
                        <span class="training-calendar-event-stack">
                            @foreach ($dayEvents->take(3) as $session)
                                <span class="training-calendar-event is-{{ strtolower($session['period']) }}" title="{{ $session['calendar_title'] ?? $session['title'] }} — {{ $session['time_range'] }}">
                                    <span class="training-calendar-event-dot" aria-hidden="true"></span>
                                    <span class="training-calendar-event-label">{{ $session['time'] }} {{ $showBatch ? $session['batch'] : $session['period'] }}</span>
                                </span>
                            @endforeach
                            @if ($dayEvents->count() > 3)
                                <span class="training-calendar-more">+{{ $dayEvents->count() - 3 }} more</span>
                            @endif
                        </span>
                    </button>
                @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <aside id="{{ $calendarId }}-agenda" class="training-calendar-agenda" aria-live="polite">
            @foreach ($calendarDays as $day)
                @php
                    $dateKey = $day->toDateString();
                    $dayEvents = $eventsByDate->get($dateKey, collect());
                    $isSelected = $dateKey === $initialDate;
                @endphp
                <section
                    class="training-calendar-agenda-panel {{ $isSelected ? 'is-active' : '' }}"
                    data-calendar-agenda="{{ $dateKey }}"
                    @if(! $isSelected) hidden @endif
                >
                    <div class="training-calendar-agenda-heading">
                        <div>
                            <p>{{ $day->format('l') }}</p>
                            <h3>{{ $day->format('F j, Y') }}</h3>
                        </div>
                        <span>{{ $dayEvents->count() }} {{ \Illuminate\Support\Str::plural('session', $dayEvents->count()) }}</span>
                    </div>

                    <div class="training-calendar-agenda-list">
                        @forelse ($dayEvents as $session)
                            <article class="training-calendar-agenda-session is-{{ strtolower($session['period']) }}">
                                <div class="training-calendar-session-time">
                                    <strong>{{ $session['period'] }}</strong>
                                    <time datetime="{{ $session['starts_at']->toIso8601String() }}">{{ $session['time_range'] }}</time>
                                </div>
                                <h4>{{ $session['title'] }}</h4>
                                @if ($showBatch)
                                    <p class="training-calendar-session-batch">{{ $session['batch'] }}</p>
                                @endif
                                <p class="training-calendar-session-room">
                                    <x-dashboard-icon name="location-dot" />
                                    <span>{{ $session['room'] }}</span>
                                </p>
                                @if ($editable)
                                    <a href="{{ route('admin.schedules.edit', ['trainingBatch' => $session['batch_id'], 'month' => $month->format('Y-m'), 'date' => $dateKey]) }}" class="training-calendar-edit-link">Edit recurring schedule</a>
                                @endif
                            </article>
                        @empty
                            <div class="training-calendar-empty">
                                <x-dashboard-icon name="calendar-days" />
                                <p>{{ $emptyMessage }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </aside>
    </div>
</section>
