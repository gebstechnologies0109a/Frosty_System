@extends('layouts.app')
@section('title', 'Preview: '.$page->title)
@section('content')
@include('admin.partials.page-header', [
    'title' => 'Preview: '.$page->title,
    'subtitle' => '/p/'.$page->slug,
    'actions' => '<a href="'.route('admin.page-builder.edit', $page).'" class="btn btn-outline-secondary">Edit</a>
        <a href="'.route('pages.show', $page->slug).'" class="btn btn-primary" target="_blank">Open live</a>',
])
<div class="card border-0 shadow-sm">
    <div class="card-body">{!! $html !!}</div>
</div>
@endsection
