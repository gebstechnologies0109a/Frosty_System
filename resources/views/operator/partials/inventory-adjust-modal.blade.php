<div class="modal fade frosty-modal" id="adjustModal" tabindex="-1" aria-labelledby="adjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('operator.supplies-inventory.adjust') }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="adjustModalLabel"><i class="fa-solid fa-sliders me-2"></i>Adjust inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3 text-muted small">Product</p>
                <p class="fs-5 fw-semibold mb-3" id="adj-product-name">—</p>
                <input type="hidden" name="product_id" id="adj-product-id">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="adj-mode">Adjustment type</label>
                    <select name="mode" id="adj-mode" class="form-select" required>
                        <option value="set">Set stock level</option>
                        <option value="increase">Increase</option>
                        <option value="decrease">Decrease</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="adj-amount">Quantity</label>
                    <input type="number" name="amount" id="adj-amount" class="form-control form-control-lg" min="0" required value="0">
                    <div class="form-text">Current stock: <strong id="adj-current-stock">0</strong></div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="adj-min-stock">Minimum stock level</label>
                    <input type="number" name="minimum_stock" id="adj-min-stock" class="form-control" min="0" placeholder="Default: 10">
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-frosty"><i class="fa-solid fa-check me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
