<?php

namespace App\Services;

use App\Models\School;
use App\Models\StaffInvite;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StaffImportService
{
    public const HEADERS = [
        'name' => ['name', 'full name', 'staff name'],
        'email' => ['email', 'e-mail'],
        'phone' => ['phone', 'mobile', 'phone number'],
        'role' => ['role', 'type'],
        'subjects' => ['subjects', 'subject'],
    ];

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
                'name' => $cols[0] ?? '',
                'email' => $cols[1] ?? '',
                'phone' => $cols[2] ?? '',
                'role' => $cols[3] ?? 'teaching',
                'subjects' => $cols[4] ?? '',
            ];
        }

        return $rows;
    }

    public function parseCsv(string $contents): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($contents));
        if (! $lines || count($lines) < 2) {
            return [];
        }
        $headers = [];
        foreach (str_getcsv(array_shift($lines)) as $cell) {
            $n = strtolower(trim($cell));
            $mapped = null;
            foreach (self::HEADERS as $key => $aliases) {
                if ($n === $key || in_array($n, $aliases, true)) {
                    $mapped = $key;
                    break;
                }
            }
            $headers[] = $mapped;
        }
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

    public function preview(School $school, array $rows): array
    {
        $seen = [];
        $out = [];
        $errors = [];
        foreach ($rows as $i => $row) {
            $n = $i + 1;
            foreach ($row as $k => $v) {
                if (is_string($v)) {
                    $row[$k] = trim($v);
                }
            }
            $row['_ok'] = true;
            $row['_error'] = null;
            $email = strtolower((string) ($row['email'] ?? ''));
            $row['email'] = $email;
            if (trim((string) ($row['name'] ?? '')) === '') {
                $row['_ok'] = false;
                $row['_error'] = 'Missing name';
            }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $row['_ok'] = false;
                $row['_error'] = $row['_error'] ?: 'Bad email';
            } elseif (isset($seen[$email]) || User::where('email', $email)->exists() || StaffInvite::where('school_id', $school->id)->where('email', $email)->whereNull('accepted_at')->exists()) {
                $row['_ok'] = false;
                $row['_error'] = $row['_error'] ?: 'Duplicate email';
            }
            $seen[$email] = true;
            $role = strtolower((string) ($row['role'] ?? 'teaching'));
            $row['role'] = in_array($role, ['non-teaching', 'nonteaching', 'staff'], true) ? 'nonteaching' : 'teaching';
            $row['usergroup_id'] = $row['role'] === 'nonteaching' ? User::NON_TEACHING_USERGROUP_ID : User::TEACHER_USERGROUP_ID;
            if (! $row['_ok']) {
                $errors[$n] = $row['_error'];
            }
            $out[] = $row;
        }

        return ['rows' => $out, 'errors' => $errors];
    }

    public function confirm(School $school, User $admin, array $rows): array
    {
        $join = app(SchoolJoinService::class);
        $created = 0;
        $skipped = 0;
        foreach ($rows as $row) {
            if (empty($row['_ok'])) {
                $skipped++;
                continue;
            }
            try {
                $join->inviteStaff($admin, [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'] ?? null,
                    'usergroup_id' => $row['usergroup_id'] ?? User::TEACHER_USERGROUP_ID,
                    'subjects' => $this->subjectList($row['subjects'] ?? ''),
                ]);
                $created++;
            } catch (ValidationException $e) {
                $skipped++;
            }
        }

        return compact('created', 'skipped');
    }

    public function templateCsv(): string
    {
        return "name,email,phone,role,subjects\n";
    }

    private function subjectList($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/[;,]/', (string) $value))));
    }
}
