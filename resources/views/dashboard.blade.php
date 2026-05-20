@extends('layouts.frosty')

@section('title', 'Frosty Dashboard')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1">Frosty Rewards Dashboard</h1>
            <p class="text-muted mb-0">
                {{ $rules['points_per_kilo'] }} pts/kg direct ·
                {{ $rules['qualification_kilos'] }} kg/month to qualify for override ·
                {{ $rules['override_per_kilo'] }} pts/kg override (L2–L4)
            </p>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted small">Members</div>
                <div class="fs-3 fw-bold">{{ $stats['members'] }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted small">Kilos (this month)</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['kilos_this_month'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted small">Points (this month)</div>
                <div class="fs-3 fw-bold">{{ number_format($stats['points_this_month'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3">
                <div class="text-muted small">Override qualified</div>
                <div class="fs-3 fw-bold">{{ $stats['qualified_members'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Recent kilo purchases</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Store</th>
                                <th class="text-end">Kg</th>
                                <th class="text-end">Pts</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentPurchases as $purchase)
                                <tr>
                                    <td>{{ $purchase->purchased_at->format('M j') }}</td>
                                    <td>{{ $purchase->member->name }}</td>
                                    <td>{{ $purchase->store->name }}</td>
                                    <td class="text-end">{{ number_format($purchase->kilos, 2) }}</td>
                                    <td class="text-end">{{ number_format($purchase->direct_points, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No purchases yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white fw-semibold">Monthly member summary</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th class="text-end">Kg</th>
                                <th class="text-end">Override</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monthlySummaries as $row)
                                <tr>
                                    <td>{{ $row->member->name }}</td>
                                    <td class="text-end">{{ number_format($row->total_kilos, 2) }}</td>
                                    <td class="text-end">{{ number_format($row->total_override_points, 2) }}</td>
                                    <td>
                                        @if ($row->override_qualified)
                                            <span class="badge text-bg-success">Qualified</span>
                                        @else
                                            <span class="badge text-bg-secondary">Not eligible</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No monthly data yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <a href="{{ route('store.kilos.create') }}" class="btn btn-primary">Record kilos at store portal</a>
    </div>
@endsection
