<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\StaffImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffImportController extends Controller
{
    public function bulk()
    {
        return view('admin.teacher.import', [
            'school' => Auth::user()->school,
            'mode' => 'bulk',
        ]);
    }

    public function import()
    {
        return view('admin.teacher.import', [
            'school' => Auth::user()->school,
            'mode' => 'csv',
        ]);
    }

    public function preview(Request $request, StaffImportService $import)
    {
        $school = Auth::user()->school;
        $data = $request->validate([
            'mode' => 'required|in:bulk,csv',
            'grid' => 'nullable|string',
            'file' => 'nullable|file',
        ]);
        $rows = $request->hasFile('file')
            ? $import->parseCsv($request->file('file')->get())
            : $import->parseGrid((string) ($data['grid'] ?? ''));
        $preview = $import->preview($school, $rows);
        session(['staff_import' => $preview['rows']]);

        return view('admin.teacher.import_preview', [
            'school' => $school,
            'preview' => $preview,
        ]);
    }

    public function confirm(StaffImportService $import)
    {
        $rows = session('staff_import');
        if (! $rows) {
            return redirect('/admin/teachers')->with('failmessage', 'That import expired. Paste the list again.');
        }
        $result = $import->confirm(Auth::user()->school, Auth::user(), $rows);
        session()->forget('staff_import');

        return redirect('/admin/teachers')->with('successmessage', $result['created'].' invites sent, '.$result['skipped'].' skipped.');
    }

    public function template(StaffImportService $import)
    {
        return response($import->templateCsv(), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff-template.csv"',
        ]);
    }
}
