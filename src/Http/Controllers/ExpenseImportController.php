<?php

namespace ME\AccSfl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ME\AccSfl\Services\ExpenseImportService;

class ExpenseImportController extends Controller
{
    public function __construct(private readonly ExpenseImportService $importer)
    {
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorize('ac_expense.add');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function save(Request $request): JsonResponse
    {
        $this->authorize('ac_expense.add');

        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls']]);

        return response()->json($this->importer->import($request->file('file')));
    }
}
