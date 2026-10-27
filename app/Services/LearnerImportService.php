<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\Admission;
use App\Models\School;
use App\Models\StandardLink;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LearnerImportService
{
    public const HEADERS = [
        'first_name' => ['first name', 'firstname', 'first'],
        'last_name' => ['last name', 'lastname', 'surname', 'last'],
        'other_names' => ['other names', 'other name', 'middle name'],
        'gender' => ['gender', 'sex'],
        'admission_no' => ['admission no', 'admission number', 'adm no', 'admission_no'],
        'email' => ['email', 'e-mail'],
        'phone' => ['phone', 'mobile', 'phone number'],
        'parent_name' => ['parent name', 'parent'],
        'parent_email' => ['parent email'],
        'parent_phone' => ['parent phone'],
        'relationship' => ['relationship', 'relation'],
        'class' => ['class', 'class/arm', 'cohort'],
    ];

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function preview(School $school, array $rows, ?StandardLink $forcedClass = null, ?int $levelId = null): array
    {
        $year = SiteHelper::getAcademicYear($school->id);
        $classes = StandardLink::with('standard', 'section')
            ->where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->get();
        $byName = [];
        foreach ($classes as $class) {
            $byName[strtolower($class->StandardSection)] = $class;
            $byName[strtolower(trim($class->standard->name.' '.$class->section->name))] = $class;
        }
        $seenAdm = [];
        $seenEmail = [];
        $out = [];
        $errors = [];
        foreach ($rows as $i => $row) {
            $n = $i + 1;
            $row = $this->normalize($row);
            $row['_ok'] = true;
            $row['_error'] = null;
            $row['_class'] = $forcedClass;
            if ($forcedClass) {
                $row['_class'] = $forcedClass;
                $row['_class_id'] = $forcedClass->id;
                $row['class'] = $forcedClass->StandardSection;
            } elseif ($levelId) {
                $row['_level_id'] = $levelId;
            } else {
                $label = strtolower(trim((string) ($row['class'] ?? '')));
                $row['_class'] = $byName[$label] ?? null;
                $row['_class_id'] = $row['_class']->id ?? null;
                if (! $row['_class']) {
                    $row['_ok'] = false;
                    $row['_error'] = 'Unknown class '.$row['class'];
                }
            }
            if (trim((string) ($row['first_name'] ?? '')) === '') {
                $row['_ok'] = false;
                $row['_error'] = 'Missing first name';
            }
            $adm = strtoupper(trim((string) ($row['admission_no'] ?? '')));
            if ($adm !== '') {
                if (isset($seenAdm[$adm]) || User::where('school_id', $school->id)->where('registration_number', $adm)->exists()) {
                    $row['_ok'] = false;
                    $row['_error'] = 'Duplicate admission number';
                }
                $seenAdm[$adm] = true;
            }
            $email = strtolower(trim((string) ($row['email'] ?? '')));
            if ($email !== '') {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seenEmail[$email]) || User::where('email', $email)->exists()) {
                    $row['_ok'] = false;
                    $row['_error'] = $row['_error'] ?: 'Bad or duplicate email';
                }
                $seenEmail[$email] = true;
            }
            $g = strtolower((string) ($row['gender'] ?? ''));
            $row['gender'] = in_array($g, ['m', 'male'], true) ? 'male' : (in_array($g, ['f', 'female'], true) ? 'female' : ($g ?: null));
            if (! $row['_ok']) {
                $errors[$n] = $row['_error'];
            }
            $out[] = $row;
        }

        return ['rows' => $out, 'errors' => $errors];
    }

    public function confirm(School $school, array $rows): array
    {
        $enroll = app(LearnerEnrollmentService::class);
        $slips = [];
        $created = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            $classId = $row['_class_id'] ?? optional($row['_class'] ?? null)->id;
            $class = $classId ? StandardLink::where('id', $classId)->where('school_id', $school->id)->first() : null;
            if (empty($row['_ok']) || ! $class) {
                $skipped++;
                continue;
            }
            try {
                $result = $enroll->enroll($school, $row, $class);
                if ($result['pin'] !== '') {
                    $slips[] = LearnerEnrollmentService::slip($result['user'], $result['pin'], $class->StandardSection);
                }
                if ($result['parent'] && $result['parent_pin']) {
                    $slips[] = LearnerEnrollmentService::slip($result['parent'], $result['parent_pin'], 'Parent');
                }
                $created++;
            } catch (ValidationException $e) {
                $skipped++;
            }
        }

        return compact('created', 'skipped', 'slips');
    }

    public function parseCsv(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        if (! $lines || count($lines) < 2) {
            return [];
        }
        $headers = $this->mapHeaders(str_getcsv(array_shift($lines)));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            $row = [];
            foreach ($headers as $i => $key) {
                if ($key) {
                    $row[$key] = $cols[$i] ?? '';
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function parseGrid(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($text));
        $rows = [];
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cols = preg_split('/\t|,/', $line);
            $rows[] = [
                'first_name' => $cols[0] ?? '',
                'last_name' => $cols[1] ?? '',
                'gender' => $cols[2] ?? '',
                'admission_no' => $cols[3] ?? '',
                'email' => $cols[4] ?? '',
                'phone' => $cols[5] ?? '',
                'parent_name' => $cols[6] ?? '',
                'parent_email' => $cols[7] ?? '',
                'parent_phone' => $cols[8] ?? '',
            ];
        }

        return $rows;
    }

    public function allocate(array $rows, $arms, string $mode = 'auto'): array
    {
        $arms = collect($arms)->values();
        if ($arms->isEmpty()) {
            return $rows;
        }
        if ($mode === 'alpha') {
            usort($rows, fn ($a, $b) => strcasecmp(($a['last_name'] ?? '').($a['first_name'] ?? ''), ($b['last_name'] ?? '').($b['first_name'] ?? '')));
        }
        $counts = $arms->mapWithKeys(fn ($a) => [$a->id => ['male' => 0, 'female' => 0, 'other' => 0]])->all();
        $i = 0;
        foreach ($rows as &$row) {
            if (! empty($row['_class']) || ! empty($row['_class_id'])) {
                continue;
            }
            if ($mode === 'manual' && ! empty($row['arm_id'])) {
                $row['_class'] = $arms->firstWhere('id', (int) $row['arm_id']);
            } elseif ($mode === 'gender') {
                $g = in_array($row['gender'] ?? '', ['male', 'female'], true) ? $row['gender'] : 'other';
                $row['_class'] = $arms->sortBy(fn ($a) => $counts[$a->id][$g] ?? 0)->values()->first();
                if ($row['_class']) {
                    $counts[$row['_class']->id][$g]++;
                }
            } else {
                $row['_class'] = $arms[$i % $arms->count()];
            }
            $row['_class_id'] = $row['_class']->id ?? null;
            $i++;
        }

        return $rows;
    }

    public function parseFile(string $path, string $ext = 'csv'): array
    {
        $ext = strtolower($ext);
        if (in_array($ext, ['xlsx', 'xls'], true)) {
            $sheet = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);
            if (! $sheet) {
                return [];
            }
            $headers = $this->mapHeaders(array_map(fn ($c) => (string) $c, array_shift($sheet)));
            $rows = [];
            foreach ($sheet as $cols) {
                if (! is_array($cols) || implode('', array_map('strval', $cols)) === '') {
                    continue;
                }
                $row = [];
                foreach ($headers as $i => $key) {
                    if ($key) {
                        $row[$key] = isset($cols[$i]) ? trim((string) $cols[$i]) : '';
                    }
                }
                $rows[] = $row;
            }

            return $rows;
        }

        return $this->parseCsv((string) file_get_contents($path));
    }

    public function errorCsv(array $rows): string
    {
        $out = "row,first_name,last_name,class,error\n";
        foreach ($rows as $i => $row) {
            if (! empty($row['_ok'])) {
                continue;
            }
            $out .= implode(',', [
                $i + 1,
                '"'.str_replace('"', '""', (string) ($row['first_name'] ?? '')).'"',
                '"'.str_replace('"', '""', (string) ($row['last_name'] ?? '')).'"',
                '"'.str_replace('"', '""', (string) ($row['class'] ?? $row['_class_label'] ?? '')).'"',
                '"'.str_replace('"', '""', (string) ($row['_error'] ?? '')).'"',
            ])."\n";
        }

        return $out;
    }

    public function fromAdmissions(School $school, ?int $levelId = null): array
    {
        $year = SiteHelper::getAcademicYear($school->id);
        if (! $year) {
            return [];
        }
        $admissions = Admission::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->where('application_status', 'Approved')
            ->when($levelId, fn ($q) => $q->where('standard_id', $levelId))
            ->get();
        $rows = [];
        foreach ($admissions as $admission) {
            $name = trim((string) $admission->name);
            if ($name === '') {
                continue;
            }
            $exists = User::where('school_id', $school->id)
                ->where('usergroup_id', User::STUDENT_USERGROUP_ID)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->exists();
            if ($exists) {
                continue;
            }
            $parts = preg_split('/\s+/', $name, 2);
            $g = strtolower((string) $admission->gender);
            $rows[] = [
                'first_name' => $parts[0],
                'last_name' => $parts[1] ?? '',
                'gender' => in_array($g, ['male', 'female'], true) ? $g : null,
                'admission_no' => '',
                'email' => '',
                'phone' => '',
                'parent_name' => $admission->father_name ?: $admission->mother_name,
                'parent_email' => $admission->father_email ?: $admission->mother_email,
                'parent_phone' => ($admission->father_mobile_no && $admission->father_mobile_no !== '-') ? $admission->father_mobile_no : $admission->mother_mobile_no,
                'relationship' => $admission->father_name ? 'father' : ($admission->mother_name ? 'mother' : 'parent'),
                'class' => '',
            ];
        }

        return $rows;
    }

    public function flatten(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['_class_id'] = $row['_class']->id ?? $row['_class_id'] ?? null;
            $row['_class_label'] = $row['_class']->StandardSection ?? ($row['_class_label'] ?? $row['class'] ?? null);
            unset($row['_class']);
        }

        return $rows;
    }

    public function templateCsv(School $school): string
    {
        $headers = 'first_name,last_name,gender,admission_no,email,phone,class';
        if ($school->usesParents()) {
            $headers .= ',parent_name,parent_email,parent_phone,relationship';
        }

        return $headers."\n";
    }

    private function mapHeaders(array $raw): array
    {
        $out = [];
        foreach ($raw as $cell) {
            $n = strtolower(trim($cell));
            $mapped = null;
            foreach (self::HEADERS as $key => $aliases) {
                if ($n === $key || in_array($n, $aliases, true)) {
                    $mapped = $key;
                    break;
                }
            }
            $out[] = $mapped;
        }

        return $out;
    }

    private function normalize(array $row): array
    {
        foreach ($row as $k => $v) {
            if (is_string($v)) {
                $row[$k] = trim($v);
            }
        }

        return $row;
    }
}
