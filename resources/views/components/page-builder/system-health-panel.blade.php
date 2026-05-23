@props(['checks' => []])
@php
    $items = [
        'api' => 'API',
        'db' => 'Database',
        'queue' => 'Queue',
        'cron' => 'Scheduler',
        'ssl' => 'SSL',
    ];
    $checkList = is_array($checks) ? $checks : [];
@endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">System health</div>
    <ul class="list-group list-group-flush">
        @foreach ($items as $key => $label)
            @php $ok = (bool) ($checkList[$key] ?? false); @endphp
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ $label }}</span>
                <span class="badge text-bg-{{ $ok ? 'success' : 'secondary' }}">{{ $ok ? 'OK' : 'Off' }}</span>
            </li>
        @endforeach
    </ul>
</div>
