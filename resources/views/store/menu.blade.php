<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $operator->name }} — Menu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <header class="text-center mb-4">
        <h1 class="h3">{{ $operator->name }}</h1>
        <p class="text-muted">Products for sale</p>
    </header>
    <div class="row g-3">
        @forelse ($menu as $item)
            <div class="col-6 col-md-4">
                <div class="card h-100 shadow-sm">
                    @if ($item['image_url'])
                        <img src="{{ $item['image_url'] }}" class="card-img-top" alt="" style="height:120px;object-fit:cover">
                    @endif
                    <div class="card-body">
                        <h2 class="h6">{{ $item['name'] }}</h2>
                        @if ($item['description'])<p class="small text-muted">{{ $item['description'] }}</p>@endif
                        <p class="fs-5 fw-bold text-primary mb-0">₱{{ number_format($item['price'], 2) }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-center text-muted">No active products.</p>
        @endforelse
    </div>
</div>
</body>
</html>
