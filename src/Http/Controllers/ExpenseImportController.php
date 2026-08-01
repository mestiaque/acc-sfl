<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\AccSfl\Services\ExpenseImportService;

class ExpenseImportController extends Controller
{
    public function __construct(private readonly ExpenseImportService $importer)
    {
    }

    public function create(): View
    {
        $this->authorize('ac_expense.import');

        return view('acc-sfl::admin.expenses.import');
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('ac_expense.import');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorize('ac_expense.import');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->import($request->file('file')));
    }

    public function recheck(Request $request): JsonResponse
    {
        $this->authorize('ac_expense.import');

        $data = $request->validate(['row' => ['required']]);

        return response()->json(['row' => $data['row'], ...$this->importer->validateRow($request->input('fields', []))]);
    }
}
