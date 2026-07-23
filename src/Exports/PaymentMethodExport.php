<?php

namespace ME\AccSfl\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;

class PaymentMethodExport implements FromView
{
    public function __construct(private readonly Collection $paymentMethods)
    {
    }

    public function view(): View
    {
        return view('acc-sfl::admin.payment-methods.partials.table', ['paymentMethods' => $this->paymentMethods]);
    }
}
