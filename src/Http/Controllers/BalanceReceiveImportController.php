<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ME\AccSfl\Services\BalanceReceiveImportService;

class BalanceReceiveImportController extends Controller
{
    public function __construct(private readonly BalanceReceiveImportService $importer)
    {
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('ac_balance_receive.add');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorize('ac_balance_receive.add');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->import($request->file('file')));
    }
}
