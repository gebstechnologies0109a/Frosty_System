@php $isEdit = isset($operator); @endphp
<div class="mb-3">
    <label class="form-label">Name</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $operator->name ?? '') }}" required>
</div>
<div class="mb-3">
    <label class="form-label">Email</label>
    <input type="email" name="email" class="form-control" value="{{ old('email', $operator->email ?? '') }}" required>
</div>
@if (! $isEdit)
<div class="mb-3">
    <label class="form-label">Password</label>
    <input type="password" name="password" class="form-control" required>
</div>
@endif
<div class="mb-3">
    <label class="form-label">Distributor</label>
    <select name="distributor_id" class="form-select" required>
        @foreach ($distributors as $d)
            <option value="{{ $d->id }}" @selected((string) old('distributor_id', $operator->distributor_id ?? '') === (string) $d->id)>{{ $d->name }}</option>
        @endforeach
    </select>
</div>
@if ($isEdit)
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $operator->status->value) === $s->value)>{{ ucfirst($s->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label">Price region</label>
        <select name="region" class="form-select" required>
            @foreach ($regions as $r)
                <option value="{{ $r->value }}" @selected(old('region', $operator->region->value) === $r->value)>{{ $r->label() }}</option>
            @endforeach
        </select>
    </div>
</div>
@endif
