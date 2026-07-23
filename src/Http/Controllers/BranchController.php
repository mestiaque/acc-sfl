<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\BranchExport;
use ME\AccSfl\Http\Requests\BranchRequest;
use ME\AccSfl\Models\AcBranch;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_branch.list');

        $branches = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        return view('acc-sfl::admin.branches.index', compact('branches'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_branch.list');

        $branches = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.branches.print', compact('branches'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_branch.list');

        $branches = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new BranchExport($branches), 'branches.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcBranch::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('branch_head', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'));
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        DB::transaction(fn () => AcBranch::create($request->validated()));

        return back()->with('success', 'Branch created successfully.');
    }

    public function update(BranchRequest $request, AcBranch $branch): RedirectResponse
    {
        DB::transaction(fn () => $branch->update($request->validated()));

        return back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(AcBranch $branch): RedirectResponse
    {
        $this->authorize('ac_branch.delete');

        if ($branch->isReferenced()) {
            return back()->with('error', 'This branch cannot be deleted because it is referenced by other records.');
        }

        $branch->delete();

        return back()->with('success', 'Branch deleted successfully.');
    }
}
