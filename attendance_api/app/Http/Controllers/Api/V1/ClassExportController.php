<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Exports\ExportClassReport;
use App\Actions\Exports\TableWorkbook;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ClassExportController extends Controller
{
    public function download(Request $request, Classroom $classroom, string $kind, ExportClassReport $report, TableWorkbook $writer): BinaryFileResponse
    {
        abort_unless(in_array($kind, ['attendance', 'grades'], true), 404);
        $data = $request->validate(['template' => ['sometimes', 'boolean']]);
        $sheets = $report->build($request->user(), $classroom, $kind, (bool) ($data['template'] ?? false));
        $path = $writer->write($sheets);

        return response()->download($path, $kind.'-'.$classroom->id.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->deleteFileAfterSend(true);
    }
}
