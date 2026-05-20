@extends('layouts.frosty')

@section('title', 'Store Portal — Record Kilos')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card p-4">
                <h1 class="h4 mb-3">Record kilo purchase</h1>
                <p class="text-muted small">
                    Direct reward: <strong>2 points per kilo</strong>.
                    Upline override (L2–L4) pays <strong>0.5 points per kilo</strong> when the upline has
                    <strong>≥ 20 kg</strong> personal volume in the current calendar month.
                </p>

                <form method="post" action="{{ route('store.kilos.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="store_id" class="form-label">Store</label>
                        <select name="store_id" id="store_id" class="form-select" required>
                            <option value="">Select store…</option>
                            @foreach ($stores as $store)
                                <option value="{{ $store->id }}" @selected(old('store_id', $selectedStoreId) == $store->id)>
                                    {{ $store->name }} ({{ $store->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="member_id" class="form-label">Member</label>
                        <select name="member_id" id="member_id" class="form-select" required>
                            <option value="">Select member…</option>
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                                    {{ $member->name }} — {{ $member->member_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="kilos" class="form-label">Kilos</label>
                        <input
                            type="number"
                            name="kilos"
                            id="kilos"
                            class="form-control"
                            step="0.01"
                            min="0.01"
                            value="{{ old('kilos') }}"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (optional)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Save &amp; calculate points</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary ms-2">Dashboard</a>
                </form>
            </div>
        </div>
    </div>
@endsection
