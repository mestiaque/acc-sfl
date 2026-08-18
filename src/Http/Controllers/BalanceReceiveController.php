<?php

namespace ME\AccSfl\Http\Controllers;

use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\BalanceReceiveExport;
use ME\AccSfl\Http\Requests\BalanceReceiveRequest;
use ME\AccSfl\Models\AcAccount;
use ME\AccSfl\Models\AcBalanceReceive;
use ME\AccSfl\Models\AcBranch;
use ME\AccSfl\Models\AcMasterParticular;
use ME\AccSfl\Services\TransactionService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BalanceReceiveController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('ac_balance_receive.list');

        $balanceReceives = $this->filteredQuery($request)->latest('id')->paginate(20)->withQueryString();

        $branches = AcBranch::query()->active()->orderBy('name')->get();
        $accounts = AcAccount::query()->active()->visibleToCurrentUser()->orderBy('name')->get();
        $allowedParticularIds = AcAccount::currentUserAllowedParticularIds();
        $particulars = AcMasterParticular::query()->debit()->active()
            ->with(['particulars' => fn ($q) => $q->active()->orderBy('code')
                ->when($allowedParticularIds !== null, fn ($q2) => $q2->whereIn('id', $allowedParticularIds))])
            ->get();

        return view('acc-sfl::admin.balance-receives.index', compact('balanceReceives', 'branches', 'accounts', 'particulars'));
    }

    public function print(Request $request): View
    {
        $this->authorize('ac_balance_receive.list');

        $balanceReceives = $this->filteredQuery($request)->latest('id')->get();

        return view('acc-sfl::admin.balance-receives.print', compact('balanceReceives'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('ac_balance_receive.list');

        $balanceReceives = $this->filteredQuery($request)->latest('id')->get();

        return Excel::download(new BalanceReceiveExport($balanceReceives), 'balance-receives.xlsx');
    }

    private function filteredQuery(Request $request): Builder
    {
        return AcBalanceReceive::query()
            ->with(['branch', 'account', 'particular.masterParticular', 'creator'])
            ->when(AcAccount::currentUserTiedAccountIds(), fn ($q, $tiedIds) => $q->whereIn('account_id', $tiedIds))
            ->when($request->filled('search'), fn ($q) => $q->where('receive_no', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')))
            ->when($request->filled('master_particular_id'), fn ($q) => $q->whereHas(
                'particular',
                fn ($p) => $p->where('master_particular_id', $request->integer('master_particular_id'))
            ))
            ->when($request->filled('particular_id'), fn ($q) => $q->where('particular_id', $request->integer('particular_id')))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('receive_date', '>=', $request->date('from_date')))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('receive_date', '<=', $request->date('to_date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }

    public function store(BalanceReceiveRequest $request): RedirectResponse
    {
        $receive = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['created_by'] = $request->user()?->id;

            if ($request->hasFile('attachment')) {
                $data['attachment'] = $request->file('attachment')->store('acc-sfl/balance-receives', 'public');
            }

            return AcBalanceReceive::create($data);
        });

        app(ApprovalService::class)->request([
            'module' => 'accounts.balance_receive',
            'approvable' => $receive,
            'title' => "Balance Receive Approval - {$receive->receive_no}",
            'description' => "{$receive->creator?->name} recorded a balance receive of {$receive->amount}.",
            'route_name' => 'acc-sfl.balance-receives.index',
            'requested_by' => $request->user()?->id,
        ]);

        return back()->with('success', 'Balance receive recorded successfully and sent for approval.');
    }

    public function update(BalanceReceiveRequest $request, AcBalanceReceive $balanceReceive): RedirectResponse
    {
        DB::transaction(function () use ($request, $balanceReceive) {
            $data = $request->validated();

            if ($request->hasFile('attachment')) {
                if ($balanceReceive->attachment) {
                    Storage::disk('public')->delete($balanceReceive->attachment);
                }
                $data['attachment'] = $request->file('attachment')->store('acc-sfl/balance-receives', 'public');
            }

            $balanceReceive->update($data);
        });

        return back()->with('success', 'Balance receive updated successfully.');
    }

    public function destroy(AcBalanceReceive $balanceReceive): RedirectResponse
    {
        $this->authorize('ac_balance_receive.delete');

        if ($balanceReceive->status === AcBalanceReceive::STATUS_APPROVED) {
            return back()->with('error', 'Approved balance receives cannot be deleted normally — use Force Delete.');
        }

        if ($balanceReceive->isReferenced()) {
            return back()->with('error', 'This balance receive cannot be deleted because it already has a posted transaction.');
        }

        $balanceReceive->delete();

        return back()->with('success', 'Balance receive deleted successfully.');
    }

    /**
     * Reverses the posted ledger transaction (restoring the account balance) and
     * permanently removes the balance receive - the only way to remove an
     * already-approved receive, since normal delete() is blocked once it has a
     * posted transaction.
     */
    public function forceDestroy(AcBalanceReceive $balanceReceive): RedirectResponse
    {
        $this->authorize('ac_balance_receive.force_delete');

        if ($balanceReceive->status !== AcBalanceReceive::STATUS_APPROVED) {
            return back()->with('error', 'Only approved balance receives need Force Delete — use the normal Delete action instead.');
        }

        DB::transaction(function () use ($balanceReceive) {
            $transactions = $balanceReceive->transactions()->get();

            if ($transactions->isNotEmpty()) {
                app(TransactionService::class)->reverseTransactions($balanceReceive->account, $transactions);
            }

            if ($balanceReceive->attachment) {
                Storage::disk('public')->delete($balanceReceive->attachment);
            }

            $balanceReceive->forceDelete();
        });

        return back()->with('success', 'Balance receive permanently deleted and its ledger entry reversed.');
    }

    public function approve(AcBalanceReceive $balanceReceive, Request $request): RedirectResponse
    {
        $this->authorize('ac_balance_receive.approve');

        abort_if($balanceReceive->status !== AcBalanceReceive::STATUS_PENDING, 403, 'Only pending balance receives can be approved.');

        $approval = $this->findPendingApproval($balanceReceive);

        if (!$approval) {
            return back()->with('error', 'No pending approval request found for this balance receive.');
        }

        app(ApprovalService::class)->approve($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', 'Balance receive approved successfully.');
    }

    public function reject(AcBalanceReceive $balanceReceive, Request $request): RedirectResponse
    {
        $this->authorize('ac_balance_receive.approve');

        abort_if($balanceReceive->status !== AcBalanceReceive::STATUS_PENDING, 403, 'Only pending balance receives can be rejected.');

        $request->validate(['remarks' => ['required', 'string', 'max:1000']]);

        $approval = $this->findPendingApproval($balanceReceive);

        if (!$approval) {
            return back()->with('error', 'No pending approval request found for this balance receive.');
        }

        app(ApprovalService::class)->reject($approval, $request->user(), $request->input('remarks'));

        return back()->with('success', 'Balance receive rejected.');
    }

    private function findPendingApproval(AcBalanceReceive $balanceReceive): ?Approval
    {
        return Approval::where('module', 'accounts.balance_receive')
            ->where('approvable_type', AcBalanceReceive::class)
            ->where('approvable_id', $balanceReceive->id)
            ->where('status', 'pending')
            ->first();
    }
}
