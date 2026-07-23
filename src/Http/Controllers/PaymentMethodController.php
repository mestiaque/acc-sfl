<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\PaymentMethodExport;
use ME\AccSfl\Http\Requests\PaymentMethodRequest;
use ME\AccSfl\Models\AcPaymentMethod;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentMethodController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_payment_method.list');

        $paymentMethods = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        return view('acc-sfl::admin.payment-methods.index', compact('paymentMethods'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_payment_method.list');

        $paymentMethods = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.payment-methods.print', compact('paymentMethods'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_payment_method.list');

        $paymentMethods = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new PaymentMethodExport($paymentMethods), 'payment-methods.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcPaymentMethod::query()
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));
    }

    public function store(PaymentMethodRequest $request): RedirectResponse
    {
        DB::transaction(fn () => AcPaymentMethod::create($request->validated()));

        return back()->with('success', 'Payment method created successfully.');
    }

    public function update(PaymentMethodRequest $request, AcPaymentMethod $paymentMethod): RedirectResponse
    {
        DB::transaction(fn () => $paymentMethod->update($request->validated()));

        return back()->with('success', 'Payment method updated successfully.');
    }

    public function destroy(AcPaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize('ac_payment_method.delete');

        if ($paymentMethod->isReferenced()) {
            return back()->with('error', 'This payment method cannot be deleted because it is referenced by other records.');
        }

        $paymentMethod->delete();

        return back()->with('success', 'Payment method deleted successfully.');
    }
}
