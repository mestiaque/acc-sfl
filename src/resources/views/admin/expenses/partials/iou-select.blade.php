{{--
    "Against IOU" picker shared by the Expense create and edit pages. An expense bought with an
    IOU posts no cash of its own (the cash left at IOU issue); the difference is settled when
    the IOU is adjusted. Picking an IOU locks Employee and Account to the IOU's.
    $selectedIouId is the expense's current iou_id on edit.
--}}
<div class="row">
    <div class="col-md-6 form-group">
        <label>Against IOU</label>
        <select name="iou_id" id="expenseIou" class="form-control">
            <option value="">-- None (paid directly from account) --</option>
            @foreach($ious as $iou)
            @php $spent = (float) ($iou->approved_expense_total ?? 0); @endphp
            <option value="{{ $iou->id }}"
                data-employee-id="{{ $iou->employee_id }}"
                data-account-id="{{ $iou->account_id }}"
                @selected((int) ($selectedIouId ?? 0) === $iou->id)>
                {{ $iou->iou_no }} - {{ $iou->employee->name ?? '-' }} - Tk {{ number_format((float) $iou->amount, 2) }} (spent {{ number_format($spent, 2) }}, left {{ number_format((float) $iou->amount - $spent, 2) }})
            </option>
            @endforeach
        </select>
        <small class="text-muted">Pick the IOU this purchase was made with. Its cash was already paid out at IOU issue, so this expense won't reduce the balance again — any difference is settled when the IOU is adjusted.</small>
    </div>
</div>

@push('js')
<script>
    $(function () {
        function acSyncExpenseIou() {
            var $option = $('#expenseIou option:selected');
            var hasIou = $('#expenseIou').val() !== '';

            if (hasIou) {
                $('#expenseEmployee').val(String($option.data('employee-id'))).trigger('change');
                var $account = $('#expenseAccount');
                if (!$account.prop('disabled')) {
                    $account.val(String($option.data('account-id'))).trigger('change');
                }
            }

            // The server takes Employee from the IOU, so it isn't editable while one is picked.
            $('#expenseEmployee').prop('disabled', hasIou);
        }

        $('#expenseIou').on('change', acSyncExpenseIou);
        acSyncExpenseIou();
    });
</script>
@endpush
