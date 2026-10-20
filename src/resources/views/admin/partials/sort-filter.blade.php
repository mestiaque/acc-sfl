{{-- Sort By / Order selects for a list filter form; see Concerns\SortsListing. --}}
<div class="col-md-2 mb-2">
    <label class="form-label mb-1">Sort By</label>
    <select name="sort_by" class="form-control form-control-sm">
        <option value="date" @selected(request('sort_by', 'date') === 'date')>Date</option>
        <option value="no" @selected(request('sort_by') === 'no')>{{ $noLabel ?? 'No.' }}</option>
    </select>
</div>
<div class="col-md-2 mb-2">
    <label class="form-label mb-1">Order</label>
    <select name="sort_dir" class="form-control form-control-sm">
        <option value="desc" @selected(request('sort_dir', 'desc') === 'desc')>Descending</option>
        <option value="asc" @selected(request('sort_dir') === 'asc')>Ascending</option>
    </select>
</div>
