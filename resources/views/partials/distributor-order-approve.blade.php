@if ($order->status === \App\Enums\OrderStatus::Pending)
    @if ($order->requiresPaymentProof() && ! $order->hasPaymentProof())
        <span class="text-muted small">Awaiting payment proof</span>
    @else
        <form method="post" action="{{ route('distributor.orders.approve', $order) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success">Approve</button>
        </form>
    @endif
@endif
