@props(['view' => null, 'edit' => null, 'delete' => null, 'extra' => null])
<div class="d-flex flex-wrap gap-1 justify-content-end">
    @if ($view)<a href="{{ $view }}" class="btn btn-sm btn-outline-primary">View</a>@endif
    @if ($edit)<a href="{{ $edit }}" class="btn btn-sm btn-outline-secondary">Edit</a>@endif
    @if ($extra){!! $extra !!}@endif
    @if ($delete)
        <form method="post" action="{{ $delete }}" class="d-inline" onsubmit="return confirm('Delete this record?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
        </form>
    @endif
</div>
