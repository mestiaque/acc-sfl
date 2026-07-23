<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\MasterParticularExport;
use ME\AccSfl\Http\Requests\MasterParticularRequest;
use ME\AccSfl\Models\AcMasterParticular;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MasterParticularController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_master_particular.list');

        $masterParticulars = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        return view('acc-sfl::admin.master-particulars.index', compact('masterParticulars'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_master_particular.list');

        $masterParticulars = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.master-particulars.print', compact('masterParticulars'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_master_particular.list');

        $masterParticulars = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new MasterParticularExport($masterParticulars), 'master-particulars.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcMasterParticular::query()
            ->withCount('particulars')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));
    }

    public function store(MasterParticularRequest $request): RedirectResponse
    {
        DB::transaction(fn () => AcMasterParticular::create($request->validated()));

        return back()->with('success', 'Master particular created successfully.');
    }

    public function update(MasterParticularRequest $request, AcMasterParticular $masterParticular): RedirectResponse
    {
        DB::transaction(fn () => $masterParticular->update($request->validated()));

        return back()->with('success', 'Master particular updated successfully.');
    }

    public function destroy(AcMasterParticular $masterParticular): RedirectResponse
    {
        $this->authorize('ac_master_particular.delete');

        if ($masterParticular->isReferenced()) {
            return back()->with('error', 'This master particular cannot be deleted because it has particulars under it.');
        }

        $masterParticular->delete();

        return back()->with('success', 'Master particular deleted successfully.');
    }
}
