{{--
    Shared line-item row (table row) for the Expense create and edit pages.
    $index is either an integer (real row) or the literal string '__INDEX__'
    (the JS clone template — see acAddExpenseItemRow in item-scripts.blade.php).
    $item is an optional AcExpenseDetail for prefill on edit. A prefilled row is treated
    as having a manually-entered amount whenever its stored amount doesn't match qty*rate
    (there's no separate "was manual" flag persisted, so this is inferred on load).
--}}
@php
    $isManualAmount = isset($item) && abs((float) $item->amount - ((float) $item->qty * (float) $item->rate)) > 0.001;
@endphp
<tr class="expense-item-row" data-index="{{ $index }}">
    <td style="min-width: 220px;">
        <select name="items[{{ $index }}][particular_id]" class="form-control form-control-sm" required>
            <option value="">-- Select --</option>
            @foreach($particulars as $master)
            <optgroup label="{{ $master->name }}">
                @foreach($master->particulars as $particular)
                <option value="{{ $particular->id }}" @selected(isset($item) && (int) $item->particular_id === $particular->id)>{{ $particular->code ? "{$particular->code} - " : '' }}{{ $particular->name }}</option>
                @endforeach
            </optgroup>
            @endforeach
        </select>
    </td>
    <td style="min-width: 100px;">
        <input type="text" name="items[{{ $index }}][uom]" class="form-control form-control-sm item-uom" value="{{ $item->uom ?? '' }}" @disabled($isManualAmount)>
    </td>
    <td style="min-width: 100px;">
        <input type="number" step="0.01" min="0.01" name="items[{{ $index }}][qty]" class="form-control form-control-sm item-qty" value="{{ $item->qty ?? '' }}" @disabled($isManualAmount)>
    </td>
    <td style="min-width: 100px;">
        <input type="number" step="0.01" min="0" name="items[{{ $index }}][rate]" class="form-control form-control-sm item-rate" value="{{ $item->rate ?? '0' }}" @disabled($isManualAmount)>
    </td>
    <td style="min-width: 120px;">
        <input type="number" step="0.01" min="0" name="items[{{ $index }}][amount]" class="form-control form-control-sm item-amount" value="{{ $item->amount ?? '0.00' }}" @unless($isManualAmount) readonly @endunless>
    </td>
    <td class="text-center">
        <input type="checkbox" class="item-manual-amount" title="Enter amount manually" @checked($isManualAmount)>
    </td>
    <td class="text-center">
        <button type="button" class="btn btn-outline-danger btn-sm remove-expense-item-row" title="Remove line"><i class="fa-solid fa-minus"></i></button>
    </td>
</tr>
