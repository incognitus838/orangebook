<?php

namespace App\Support;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;

class AdmissionNumber
{
    public static function prefix(School $school): string
    {
        $kind = $school->kind ?? 'school';
        if ($kind === 'ngo') {
            return 'PRG';
        }
        if ($kind === 'training') {
            return 'TRN';
        }
        $source = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($school->slug ?: $school->name)));
        $source = $source !== '' ? $source : 'SCH';

        return substr($source, 0, 3);
    }

    public static function yearCode(): string
    {
        return date('y');
    }

    public static function next(School $school): string
    {
        $prefix = self::prefix($school).'/'.self::yearCode().'/';
        $latest = User::withTrashed()
            ->where('school_id', $school->id)
            ->where('registration_number', 'like', $prefix.'%')
            ->orderBy('registration_number', 'desc')
            ->value('registration_number');

        $seq = 1;
        if (is_string($latest) && preg_match('/\/(\d+)$/', $latest, $m)) {
            $seq = ((int) $m[1]) + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public static function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : Str::upper($value);
    }
}
