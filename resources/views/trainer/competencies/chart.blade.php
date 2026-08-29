@extends('trainer.layouts.app', ['title' => ucfirst($chart).' Chart | MCARE Trainer'])

@section('content')
@php
    $statusMark = fn (?string $status) => match ($status) {
        \App\Models\TraineeCompetencyRecord::STATUS_COMPETENT => 'C',
        \App\Models\TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT => 'NYC',
        \App\Models\TraineeCompetencyRecord::STATUS_IN_PROGRESS => 'IP',
        default => '',
    };
    $categoryLabels = ['basic' => 'Basic Competencies', 'common' => 'Common Competencies', 'core' => 'Core Competencies', 'custom' => 'Institutional / Custom Competencies'];
@endphp

<style>
    .record-chart { color: #172033; }
    .record-chart table { border-collapse: collapse; width: 100%; }
    .record-chart th, .record-chart td { border: 1px solid #94a3b8; padding: .35rem; text-align: center; vertical-align: middle; }
    .record-chart th { background: #f1f5f9; font-size: .68rem; font-weight: 800; }
    .record-chart td { font-size: .72rem; }
    .record-chart .trainee-name { min-width: 12rem; text-align: left; font-weight: 700; }
    .record-chart .unit-heading { min-width: 5rem; max-width: 8rem; }
    .record-chart .outcome-heading { min-width: 8.5rem; max-width: 12rem; text-align: left; }
    @media print {
        @page { size: A3 landscape; margin: 8mm; }
        body { background: #fff !important; }
        .dashboard-navigation-progress, .dashboard-sidebar, .dashboard-topbar, .chart-actions { display: none !important; }
        .dashboard-layout, .dashboard-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .record-chart { max-width: none !important; }
        .record-chart-section { break-after: page; }
        .record-chart-section:last-child { break-after: auto; }
    }
</style>

<section class="record-chart mx-auto max-w-none space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-300 pb-4">
        <div>
            <p class="text-xs font-bold uppercase text-violet-700">Mission Care Training Center</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950">{{ ucfirst($chart) }} Chart</h1>
            <p class="mt-1 text-sm text-slate-600">Caregiving NC II | {{ $batch->name }} {{ $batch->year }}{{ $schedule ? ' | '.$schedule.' class' : '' }}</p>
        </div>
        <div class="chart-actions flex flex-wrap gap-2">
            <a class="secondary-action" href="{{ route('trainer.competencies.index', ['batch_id' => $batch->id, 'schedule' => $schedule]) }}">Back to records</a>
            <button type="button" class="primary-action" onclick="window.print()">Print chart</button>
        </div>
    </header>

    @if($trainees->isEmpty())
        <div class="border border-slate-200 bg-white p-8 text-center text-slate-500">No approved trainees are assigned to this batch and class.</div>
    @elseif($chart === 'progress')
        <div class="overflow-x-auto bg-white">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" class="trainee-name">Trainee</th>
                        @foreach($unitsByCategory as $category => $units)
                            <th colspan="{{ $units->count() }}">{{ $categoryLabels[$category] ?? ucfirst($category) }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach($unitsByCategory as $units)
                            @foreach($units as $unit)
                                <th class="unit-heading" title="{{ $unit->title }}">{{ $unit->code }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($trainees as $trainee)
                        <tr>
                            <td class="trainee-name">{{ $trainee->last_name }}, {{ $trainee->first_name }} {{ $trainee->middle_name }}</td>
                            @foreach($unitsByCategory as $units)
                                @foreach($units as $unit)
                                    @php $record = $recordsByTrainee->get($trainee->id)?->get($unit->id); @endphp
                                    <td title="{{ $record ? \App\Models\TraineeCompetencyRecord::statuses()[$record->status] : 'Not assessed' }}">{{ $statusMark($record?->status) }}</td>
                                @endforeach
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        @foreach($unitsByCategory as $category => $units)
            <section class="record-chart-section space-y-3">
                <h2 class="text-lg font-black text-slate-950">{{ $categoryLabels[$category] ?? ucfirst($category) }}</h2>
                @foreach($units as $unit)
                    <div class="mb-5 overflow-x-auto bg-white">
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="{{ $unit->outcomes->count() + 2 }}" class="text-left">{{ $unit->code }} | {{ $unit->title }}</th>
                                </tr>
                                <tr>
                                    <th class="trainee-name">Trainee</th>
                                    @foreach($unit->outcomes as $outcome)
                                        <th class="outcome-heading">{{ $outcome->title }}</th>
                                    @endforeach
                                    <th class="unit-heading">Unit result</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trainees as $trainee)
                                    @php
                                        $record = $recordsByTrainee->get($trainee->id)?->get($unit->id);
                                        $outcomeResults = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                                    @endphp
                                    <tr>
                                        <td class="trainee-name">{{ $trainee->last_name }}, {{ $trainee->first_name }}</td>
                                        @foreach($unit->outcomes as $outcome)
                                            <td>{{ $statusMark($outcomeResults->get($outcome->id)?->status) }}</td>
                                        @endforeach
                                        <td class="font-bold">{{ $statusMark($record?->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </section>
        @endforeach
    @endif

    <footer class="text-xs text-slate-500">Legend: C - Competent | NYC - Not Yet Competent | IP - In Progress | Blank - Not Assessed</footer>
</section>
@endsection
