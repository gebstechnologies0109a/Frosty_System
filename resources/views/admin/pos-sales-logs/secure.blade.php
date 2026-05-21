@extends('layouts.app')
@section('title', 'POS Logs — Secure Access')
@section('content')
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">POS Sales Logs — Password Required</div>
            <div class="card-body">
                @if ($locked)
                    <div class="alert alert-danger">Too many failed attempts. Locked for {{ $lockMinutes }} more minute(s).</div>
                @else
                    <p class="small text-muted">Super Admin access to full POS transaction history. Enter your account password to continue.</p>
                    <form method="post" action="{{ route('admin.pos-sales-logs.unlock') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Unlock logs</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
