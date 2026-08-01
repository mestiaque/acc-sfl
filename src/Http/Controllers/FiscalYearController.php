<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\FiscalYearExport;
use ME\AccSfl\Http\Requests\FiscalYearRequest;
use ME\AccSfl\Models\AcFiscalYear;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FiscalYearController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_fiscal_year.list');

        $fiscalYears = $this->filteredQuery($request)->latest('start_year')->paginate(20)->withQueryString();

        return view('acc-sfl::admin.fiscal-years.index', compact('fiscalYears'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_fiscal_year.list');

        $fiscalYears = $this->filteredQuery($request)->latest('start_year')->get();

        return view('acc-sfl::admin.fiscal-years.print', compact('fiscalYears'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_fiscal_year.list');

        $fiscalYears = $this->filteredQuery($request)->latest('start_year')->get();

        return Excel::download(new FiscalYearExport($fiscalYears), 'fiscal-years.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcFiscalYear::query()
            ->when($request->filled('search'), fn ($q) => $q->where('label', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));
    }

    public function store(FiscalYearRequest $request): RedirectResponse
    {
        DB::transaction(fn () => AcFiscalYear::create($request->fiscalYearData()));

        return back()->with('success', 'Fiscal year created successfully.');
    }

    public function update(FiscalYearRequest $request, AcFiscalYear $fiscalYear): RedirectResponse
    {
        DB::transaction(fn () => $fiscalYear->update($request->fiscalYearData()));

        return back()->with('success', 'Fiscal year updated successfully.');
    }

    public function destroy(AcFiscalYear $fiscalYear): RedirectResponse
    {
        $this->authorize('ac_fiscal_year.delete');

        if ($fiscalYear->isReferenced()) {
            return back()->with('error', 'This fiscal year cannot be deleted because it is referenced by other records.');
        }

        $fiscalYear->delete();

        return back()->with('success', 'Fiscal year deleted successfully.');
    }
}
