@php
    use Illuminate\Support\Str;

    $formatKey = function ($key) {
        return Str::of((string) $key)->replace(['_', '-'], ' ')->title()->toString();
    };

    $formatValue = function ($value) {
        if (is_null($value) || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            if (empty($value)) {
                return '—';
            }
            if (array_is_list($value)) {
                return implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
            }
            return null;
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}/', $value)) {
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('M d, Y g:i A');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return (string) $value;
    };

    $meta = $meta ?? [];
    $hasBeforeAfter = is_array($meta)
        && array_key_exists('before', $meta)
        && array_key_exists('after', $meta)
        && is_array($meta['before'])
        && is_array($meta['after']);

    $changes = [];
    if ($hasBeforeAfter) {
        $allKeys = array_unique(array_merge(array_keys($meta['before']), array_keys($meta['after'])));
        foreach ($allKeys as $k) {
            $b = $meta['before'][$k] ?? null;
            $a = $meta['after'][$k] ?? null;
            if ($b !== $a) {
                $changes[$k] = ['before' => $b, 'after' => $a];
            }
        }
    }
@endphp

@if ($hasBeforeAfter)
    @if (empty($changes))
        <em>No field changes recorded.</em>
    @else
        @foreach ($changes as $key => $change)
            <div class="section">
                <div class="section-title">{{ $formatKey($key) }}</div>
                <div class="row"><span class="k">From:</span><span class="v from">{{ $formatValue($change['before']) ?? '(nested)' }}</span></div>
                <div class="row"><span class="k">To:</span><span class="v to">{{ $formatValue($change['after']) ?? '(nested)' }}</span></div>
            </div>
        @endforeach
    @endif
@else
    @foreach ($meta as $key => $value)
        @php $formattedValue = $formatValue($value); @endphp
        @if (is_array($value) && $formattedValue === null)
            <div class="section">
                <div class="section-title">{{ $formatKey($key) }}</div>
                @foreach ($value as $subKey => $subValue)
                    <div class="row">
                        <span class="k">{{ $formatKey($subKey) }}:</span>
                        <span class="v">{{ $formatValue($subValue) ?? '(nested)' }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div class="row">
                <span class="k">{{ $formatKey($key) }}:</span>
                <span class="v">{{ $formattedValue }}</span>
            </div>
        @endif
    @endforeach
@endif
