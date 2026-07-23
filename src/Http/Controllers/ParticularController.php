<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ParticularExport;
use ME\AccSfl\Http\Requests\ParticularRequest;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Models\AcParticular;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ParticularController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_particular.list');

        $particulars = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        $masterParticulars = AcMasterParticular::query()->active()->orderBy('name')->get();

        return view('acc-sfl::admin.particulars.index', compact('particulars', 'masterParticulars'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_particular.list');

        $particulars = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.particulars.print', compact('particulars'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_particular.list');

        $particulars = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new ParticularExport($particulars), 'particulars.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcParticular::query()
            ->with('masterParticular')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('master_particular_id'), fn ($q) => $q->where('master_particular_id', $request->integer('master_particular_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));
    }

    public function store(ParticularRequest $request): RedirectResponse
    {
        DB::transaction(fn () => AcParticular::create($request->validated()));

        return back()->with('success', 'Particular created successfully.');
    }

    public function update(ParticularRequest $request, AcParticular $particular): RedirectResponse
    {
        DB::transaction(fn () => $particular->update($request->validated()));

        return back()->with('success', 'Particular updated successfully.');
    }

    public function destroy(AcParticular $particular): RedirectResponse
    {
        $this->authorize('ac_particular.delete');

        if ($particular->isReferenced()) {
            return back()->with('error', 'This particular cannot be deleted because it is referenced by other records.');
        }

        $particular->delete();

        return back()->with('success', 'Particular deleted successfully.');
    }
}
