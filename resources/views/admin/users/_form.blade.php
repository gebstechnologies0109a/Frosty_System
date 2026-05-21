@php $isEdit = isset($user); @endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">First name</label>
        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Last name</label>
        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Profile photo</label>
        <input type="file" name="profile_photo" class="form-control" accept="image/*">
    </div>
    @if (! $isEdit)
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role" id="user-role" class="form-select" required>
            <option value="" disabled selected>Select role</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <div class="col-md-6">
        <label class="form-label">Status</label>
        <select name="status" class="form-select" required>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(old('status', $user->status->value ?? 'active') === $s->value)>{{ ucfirst($s->value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6" id="sponsor-field" style="display:none">
        <label class="form-label">Sponsor</label>
        <select name="sponsor_id" id="sponsor_id" class="form-select">
            <option value="">— None —</option>
            @foreach ($sponsors as $s)
                <option value="{{ $s->id }}" @selected((string) old('sponsor_id', $user->sponsor_id ?? '') === (string) $s->id)>{{ $s->displayName() }} ({{ $s->role->label() }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6" id="distributor-field" style="display:none">
        <label class="form-label">Distributor</label>
        <select name="distributor_id" id="distributor_id" class="form-select">
            <option value="">Select distributor</option>
            @foreach ($distributors as $d)
                <option value="{{ $d->id }}" @selected((string) old('distributor_id', $user->distributor_id ?? '') === (string) $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    @if (! $isEdit)
    <div class="col-12">
        <label class="form-label">Password</label>
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="generate_password" value="1" id="generate_password" @checked(old('generate_password'))>
            <label class="form-check-label" for="generate_password">Auto-generate password</label>
        </div>
        <input type="text" name="password" id="user-password" class="form-control" autocomplete="new-password" placeholder="Leave blank if auto-generate is checked">
        <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="btn-gen-password">Generate now</button>
    </div>
    @endif
</div>
