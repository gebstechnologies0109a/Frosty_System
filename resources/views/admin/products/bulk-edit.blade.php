<div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="bulkEditForm" method="post" action="{{ route('admin.products.bulk-update') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEditModalLabel">Bulk edit products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        <span id="bulkEditSelectedCount">0</span> product(s) selected.
                        Only fields you fill in will be updated; empty fields leave existing values unchanged.
                    </p>
                    <div id="bulkEditProductIds"></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="bulkEditCategory">Category</label>
                            <select name="category" id="bulkEditCategory" class="form-select">
                                <option value="">— no change —</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bulkEditStatus">Status</label>
                            <select name="status" id="bulkEditStatus" class="form-select">
                                <option value="">— no change —</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bulkEditPoints">Points</label>
                            <input type="number" name="points" id="bulkEditPoints" class="form-control" min="0" placeholder="—">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bulkEditPriceLuzon">Luzon price (₱)</label>
                            <input type="number" name="price_luzon" id="bulkEditPriceLuzon" class="form-control" step="0.01" min="0" placeholder="—">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bulkEditPriceDavao">Davao price (₱)</label>
                            <input type="number" name="price_davao" id="bulkEditPriceDavao" class="form-control" step="0.01" min="0" placeholder="—">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="bulkEditPriceTacloban">Tacloban price (₱)</label>
                            <input type="number" name="price_tacloban" id="bulkEditPriceTacloban" class="form-control" step="0.01" min="0" placeholder="—">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Apply changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
