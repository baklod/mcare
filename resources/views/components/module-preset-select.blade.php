@props([
    'units',
    'id',
    'placeholder' => '-- Choose from Caregiving NC II course modules or enter custom --',
])
@php
    $units = collect($units);
    $labels = \App\Models\CompetencyUnit::categoryLabels();
@endphp
<select id="{{ $id }}" {{ $attributes->class(['form-field']) }}>
    <option value="">{{ $placeholder }}</option>
    @foreach ($labels as $category => $label)
        @php $group = $units->where('category', $category); @endphp
        @if ($group->isNotEmpty())
            <optgroup label="{{ $label }}">
                @foreach ($group as $unit)
                    <option
                        value="{{ $unit->code }}"
                        data-code="{{ $unit->code }}"
                        data-title="{{ $unit->title }}"
                        data-category="{{ $unit->category }}"
                        data-hours="{{ $unit->suggestedHours() }}"
                        data-outcomes="{{ json_encode($unit->outcomeTitles()) }}"
                    >
                        [{{ $unit->code }}] {{ $unit->title }}
                    </option>
                @endforeach
            </optgroup>
        @endif
    @endforeach
</select>
