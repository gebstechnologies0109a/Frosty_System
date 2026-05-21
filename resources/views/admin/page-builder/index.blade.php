@extends('layouts.app')
@section('title', 'Page Builder')
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Page Builder',
    'subtitle' => 'Custom admin pages',
    'actions' => '<a href="'.route('admin.page-builder.create').'" class="btn btn-primary">New Page</a>',
])
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>Title</th><th>Slug</th><th>Updated</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            @forelse ($pages as $p)
                <tr>
                    <td class="fw-medium">{{ $p->title }}</td>
                    <td><code>/p/{{ $p->slug }}</code></td>
                    <td class="small text-muted">{{ $p->updated_at->format('M j, Y H:i') }}</td>
                    <td class="text-end">
                        <a href="{{ route('pages.show', $p->slug) }}" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                        <a href="{{ route('admin.page-builder.preview', $p) }}" class="btn btn-sm btn-outline-secondary">Preview</a>
                        <a href="{{ route('admin.page-builder.edit', $p) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        <form method="post" action="{{ route('admin.page-builder.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Delete this page?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No pages yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $pages->links() }}
@endsection
