@props(['source' => 'transactions', 'limit' => 5])
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between">
        <span>Activity</span>
        <span class="badge text-bg-light text-dark border">{{ $source }}</span>
    </div>
    <ul class="list-group list-group-flush">
        @for ($i = 1; $i <= min((int) $limit, 10); $i++)
            <li class="list-group-item small">
                <span class="text-muted">{{ now()->subMinutes($i * 5)->format('M j, g:i A') }}</span>
                — Sample {{ $source }} event #{{ $i }}
            </li>
        @endfor
    </ul>
</div>
