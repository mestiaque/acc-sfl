<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\AccountExport;
use ME\AccSfl\Http\Requests\AccountRequest;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcMasterParticular;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_account.list');

        $accounts = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $users = \App\Models\User::query()->orderBy('name')->get(['id', 'name']);
        $masterParticulars = AcMasterParticular::query()->active()
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')])
            ->orderBy('id')
            ->get();

        return view('acc-sfl::admin.accounts.index', compact('accounts', 'branches', 'users', 'masterParticulars'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_account.list');

        $accounts = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.accounts.print', compact('accounts'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_account.list');

        $accounts = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new AccountExport($accounts), 'accounts.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcAccount::query()
            ->with(['branch', 'user', 'particulars'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'));
    }

    public function store(AccountRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $particularIds = $data['particular_ids'] ?? [];
            unset($data['particular_ids']);

            $account = AcAccount::create($data);
            $account->particulars()->sync($particularIds);
        });

        return back()->with('success', 'Account created successfully.');
    }

    public function update(AccountRequest $request, AcAccount $account): RedirectResponse
    {
        DB::transaction(function () use ($request, $account) {
            $data = $request->validated();
            $particularIds = $data['particular_ids'] ?? [];
            unset($data['particular_ids']);

            $account->update($data);
            $account->particulars()->sync($particularIds);
        });

        return back()->with('success', 'Account updated successfully.');
    }

    public function destroy(AcAccount $account): RedirectResponse
    {
        $this->authorize('ac_account.delete');

        if ($account->isReferenced()) {
            return back()->with('error', 'This account cannot be deleted because it is referenced by other records.');
        }

        $account->delete();

        return back()->with('success', 'Account deleted successfully.');
    }
}
