<?php

namespace ME\AccSfl\Http\Controllers;

use Maatwebsite\Excel\Facades\Excel;
use ME\AccSfl\Exports\ImportTemplateExport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportTemplateController extends Controller
{
    public function download(): BinaryFileResponse
    {
        return Excel::download(new ImportTemplateExport(), 'accounts-import-template.xlsx');
    }
}
