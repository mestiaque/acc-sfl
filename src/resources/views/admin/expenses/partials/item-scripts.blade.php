{{-- Shared multi-line item behavior for the Expense create and edit pages. --}}
<script>
    var acExpenseItemIndex = {{ $nextIndex ?? 1 }};

    function acRecalculateExpenseRow(row) {
        var $row = $(row);
        if ($row.find('.item-manual-amount').is(':checked')) {
            return parseFloat($row.find('.item-amount').val()) || 0;
        }
        var qty = parseFloat($row.find('.item-qty').val()) || 0;
        var rate = parseFloat($row.find('.item-rate').val()) || 0;
        var amount = qty * rate;
        $row.find('.item-amount').val(amount.toFixed(2));
        return amount;
    }

    function acRecalculateExpenseTotal(container) {
        var total = 0;
        $(container).find('.expense-item-row').each(function () {
            total += acRecalculateExpenseRow(this);
        });
        $(container).closest('form').find('#expenseGrandTotal').text('Grand Total: ' + total.toFixed(2));
        return total;
    }

    function acToggleManualAmount(row) {
        var $row = $(row);
        var manual = $row.find('.item-manual-amount').is(':checked');
        $row.find('.item-amount').prop('readonly', !manual);
        $row.find('.item-qty, .item-rate, .item-uom').prop('disabled', manual);
    }

    function acAddExpenseItemRow(containerSelector, templateSelector) {
        var html = $(templateSelector).html().replace(/__INDEX__/g, acExpenseItemIndex++);
        $(containerSelector).append(html);
    }

    $(function () {
        var itemScope = '#expenseItemsBody';

        $(document).on('input', itemScope + ' .item-qty, ' + itemScope + ' .item-rate, ' + itemScope + ' .item-amount', function () {
            acRecalculateExpenseTotal($(this).closest(itemScope));
        });

        $(document).on('change', itemScope + ' .item-manual-amount', function () {
            var row = $(this).closest('.expense-item-row');
            acToggleManualAmount(row);
            acRecalculateExpenseTotal(row.closest(itemScope));
        });

        $('.add-expense-item-row').on('click', function () {
            acAddExpenseItemRow($(this).data('container'), '#expenseItemRowTemplate');
        });

        $(document).on('click', itemScope + ' .remove-expense-item-row', function () {
            var $container = $(this).closest(itemScope);
            if ($container.find('.expense-item-row').length > 1) {
                $(this).closest('.expense-item-row').remove();
                acRecalculateExpenseTotal($container);
            }
        });

        $(itemScope).each(function () {
            $(this).find('.expense-item-row').each(function () {
                acToggleManualAmount(this);
            });
            acRecalculateExpenseTotal(this);
        });
    });
</script>
