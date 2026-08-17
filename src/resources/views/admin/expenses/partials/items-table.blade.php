{{--
    Shared line-items table for the Expense create and edit pages.
    $particulars is required. $details (a collection of AcExpenseDetail) and
    $totalAmount are optional — pass them on edit to prefill.
--}}
<div class="d-flex justify-content-between align-items-center mb-2">
    <label class="mb-0">Line Items <span class="text-danger">*</span></label>
    <button type="button" class="btn btn-outline-primary btn-sm add-expense-item-row" data-container="#expenseItemsBody"><i class="fa-solid fa-plus"></i> Add Line</button>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered mb-2">
        <thead>
            <tr>
                <th>Particular <span class="text-danger">*</span></th>
                <th>UOM</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>Manual</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="expenseItemsBody">
            @forelse(($details ?? []) as $detail)
            @include('acc-sfl::admin.expenses.partials.item-row', ['index' => $loop->index, 'particulars' => $particulars, 'item' => $detail])
            @empty
            @include('acc-sfl::admin.expenses.partials.item-row', ['index' => 0, 'particulars' => $particulars])
            @endforelse
        </tbody>
    </table>
</div>
<template id="expenseItemRowTemplate">
    @include('acc-sfl::admin.expenses.partials.item-row', ['index' => '__INDEX__', 'particulars' => $particulars])
</template>
<div class="text-right font-weight-bold mb-3" id="expenseGrandTotal">Grand Total: {{ number_format($totalAmount ?? 0, 2) }}</div>
