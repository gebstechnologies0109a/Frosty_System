@extends('layouts.app')
@section('title', 'Page Builder')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Page Builder',
    'subtitle' => 'Edit any system or custom page layout',
    'actions' => '<a href="'.route('admin.page-builder.index').'" class="btn btn-primary">Visual builder</a>
        <a href="'.route('admin.page-builder.create').'" class="btn btn-outline-primary">Classic editor</a>
        <form method="post" action="'.route('admin.page-builder.sync').'" class="d-inline">'.csrf_field().'
        <button type="submit" class="btn btn-outline-secondary">Sync system pages</button></form>',
])
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total pages</div>
                <div class="fs-3 fw-bold">{{ $totalPages }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header fw-semibold bg-white">Block types</div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                    @foreach ($blockTypes as $type => $meta)
                        <span class="badge text-bg-light border text-dark" title="{{ $meta['function'] }}">{{ $meta['label'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@if ($pages->isEmpty())
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">All pages</div>
        <div class="card-body text-center py-5">
            <p class="text-muted mb-3">No pages in the database yet.</p>
            <form method="post" action="{{ route('admin.page-builder.sync') }}" class="d-inline">@csrf
                <button type="submit" class="btn btn-primary me-2">Sync system pages</button>
            </form>
            <a href="{{ route('admin.page-builder.create') }}" class="btn btn-outline-primary">Create new page</a>
        </div>
    </div>
@else
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">All pages ({{ $pages->count() }})</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Route / path</th>
                        <th>Status</th>
                        <th>Blocks</th>
                        <th>Last modified</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($pages as $p)
                    <tr>
                        <td class="fw-semibold">
                            {{ $p->title }}
                            @if ($p->is_system)<span class="badge text-bg-info ms-1">System</span>@endif
                        </td>
                        <td><code class="small">{{ $p->slug }}</code></td>
                        <td class="small">
                            @if ($p->route_name)
                                <div>{{ $p->route_name }}</div>
                            @endif
                            <code>{{ $p->liveUrlLabel() }}</code>
                        </td>
                        <td>
                            <span class="badge text-bg-{{ $p->pageStatus()->badgeClass() }}">{{ $p->pageStatus()->label() }}</span>
                        </td>
                        <td class="small text-muted">{{ $p->blockCount() }}</td>
                        <td class="small text-muted text-nowrap">{{ $p->updated_at->format('M j, Y g:i A') }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.page-builder.edit', $p) }}" class="btn btn-sm btn-primary">Edit</a>
                            <form method="post" action="{{ route('admin.page-builder.duplicate', $p) }}" class="d-inline">@csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Duplicate</button>
                            </form>
                            <form method="post" action="{{ route('admin.page-builder.toggle-status', $p) }}" class="d-inline">@csrf @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-warning">{{ $p->pageStatus() === \App\Enums\AdminPageStatus::Published ? 'Unpublish' : 'Publish' }}</button>
                            </form>
                            @if ($p->canOpenLive())
                                <a href="{{ $p->liveUrl() }}" class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
                            @endif
                            @if (! $p->is_system)
                                <form method="post" action="{{ route('admin.page-builder.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete this page?')">@csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
