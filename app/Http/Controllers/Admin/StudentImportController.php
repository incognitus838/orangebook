<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\SiteHelper;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Services\LearnerImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StudentImportController extends Controller
{
    public function bulk()
    {
        return $this->form('bulk');
    }

    public function import()
    {
        return $this->form('csv');
    }

    public function intake()
    {
        $school = Auth::user()->school;
        $year = SiteHelper::getAcademicYear($school->id);
        $levels = Standard::where('school_id', $school->id)->where('is_entry', 1)->where('status', 1)->orderBy('order')->get();
        if ($levels->isEmpty()) {
            $levels = Standard::where('school_id', $school->id)->where('status', 1)->orderBy('order')->get();
        }

        return view('admin.member.intake', [
            'school' => $school,
            'levels' => $levels,
            'year' => $year,
        ]);
    }

    public function preview(Request $request, LearnerImportService $import)
    {
        $school = Auth::user()->school;
        $mode = $request->input('mode', 'bulk');
        $data = $request->validate([
            'mode' => 'required|in:bulk,csv,intake',
            'class_id' => 'nullable|integer',
            'level_id' => 'nullable|integer',
            'grid' => 'nullable|string',
            'file' => 'nullable|file',
            'allocate' => 'nullable|in:auto,alpha,gender,manual',
        ]);
        $class = null;
        if (! empty($data['class_id']) && $mode !== 'intake') {
            $class = StandardLink::where('id', $data['class_id'])->where('school_id', $school->id)->firstOrFail();
        }
        $rows = [];
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $rows = $import->parseFile($file->getRealPath(), $file->getClientOriginalExtension());
        } elseif (! empty($data['grid'])) {
            $rows = $import->parseGrid((string) $data['grid']);
        }
        $arms = collect();
        if ($mode === 'intake') {
            $year = SiteHelper::getAcademicYear($school->id);
            $arms = StandardLink::with('standard', 'section')
                ->where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->where('standard_id', $data['level_id'])
                ->get();
            $fromAdmissions = $import->fromAdmissions($school, $data['level_id'] ?? null);
            $rows = array_merge($fromAdmissions, $rows);
        }
        $preview = $import->preview($school, $rows, $class, $data['level_id'] ?? null);
        if ($mode === 'intake') {
            $preview['rows'] = $import->allocate($preview['rows'], $arms, $data['allocate'] ?? 'auto');
            foreach ($preview['rows'] as &$row) {
                if ((! empty($row['_class']) || ! empty($row['_class_id'])) && empty($row['_error'])) {
                    $row['_ok'] = true;
                    $row['_error'] = null;
                }
            }
            unset($row);
        }
        $preview['rows'] = $import->flatten($preview['rows']);
        session(['learner_import' => ['mode' => $mode, 'rows' => $preview['rows'], 'level_id' => $data['level_id'] ?? null]]);

        return view('admin.member.import_preview', [
            'school' => $school,
            'preview' => $preview,
            'mode' => $mode,
            'arms' => $arms,
        ]);
    }

    public function confirm(Request $request, LearnerImportService $import)
    {
        $school = Auth::user()->school;
        $payload = session('learner_import');
        if (! $payload) {
            return redirect('/admin/students')->with('failmessage', 'That import expired. Paste the list again.');
        }
        $rows = $payload['rows'];
        if ($request->has('class_id')) {
            foreach ($rows as $i => &$row) {
                $id = (int) $request->input('class_id.'.$i);
                if ($id) {
                    $row['_class_id'] = $id;
                    if (empty($row['_error'])) {
                        $row['_ok'] = true;
                    }
                }
            }
            unset($row);
        }
        $result = $import->confirm($school, $rows);
        session()->forget('learner_import');
        session(['login_slips' => $result['slips']]);

        return redirect('/admin/students/slips')->with('successmessage', $result['created'].' added, '.$result['skipped'].' skipped. Print the login slips now.');
    }

    public function errors(LearnerImportService $import)
    {
        $payload = session('learner_import');
        $csv = $import->errorCsv($payload['rows'] ?? []);

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import-errors.csv"',
        ]);
    }

    public function template(LearnerImportService $import)
    {
        $csv = $import->templateCsv(Auth::user()->school);
        $name = Str::slug(Auth::user()->school->learnerWord()).'-template.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    private function form(string $mode)
    {
        $school = Auth::user()->school;
        $year = SiteHelper::getAcademicYear($school->id);
        $classes = $year
            ? StandardLink::with('standard', 'section')->where('school_id', $school->id)->where('academic_year_id', $year->id)->get()
            : collect();

        return view('admin.member.import', [
            'school' => $school,
            'classes' => $classes,
            'mode' => $mode,
        ]);
    }
}
