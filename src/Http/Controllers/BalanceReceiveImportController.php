<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\AccSfl\Services\BalanceReceiveImportService;

class BalanceReceiveImportController extends Controller
{
    public function __construct(private readonly BalanceReceiveImportService $importer)
    {
    }

    public function create(): View
    {
        $this->authorize('ac_balance_receive.import');

        return view('acc-sfl::admin.balance-receives.import');
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('ac_balance_receive.import');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorize('ac_balance_receive.import');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->import($request->file('file')));
    }

    public function recheck(Request $request): JsonResponse
    {
        $this->authorize('ac_balance_receive.import');

        $data = $request->validate(['row' => ['required']]);

        return response()->json(['row' => $data['row'], ...$this->importer->validateRow($request->input('fields', []))]);
    }
}
