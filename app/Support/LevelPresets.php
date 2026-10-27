<?php

namespace App\Support;

use App\Models\School;

class LevelPresets
{
    public static function levels(School $school, string $variant = 'default'): array
    {
        $kind = $school->kind ?? 'school';
        if ($kind === 'ngo') {
            return ['Cohort 1', 'Cohort 2', 'Cohort 3'];
        }
        if ($kind === 'training') {
            return ['Beginner', 'Intermediate', 'Advanced'];
        }

        $primary = ['Creche', 'Nursery 1', 'Nursery 2', 'Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'];
        $secondary = ['JSS1', 'JSS2', 'JSS3', 'SS1', 'SS2', 'SS3'];

        return match ($variant) {
            'primary' => $primary,
            'both' => array_merge($primary, $secondary),
            default => $secondary,
        };
    }

    public static function arms(School $school): array
    {
        $kind = $school->kind ?? 'school';
        if ($kind === 'ngo' || $kind === 'training') {
            return ['Morning', 'Afternoon'];
        }

        return ['A', 'B', 'Science', 'Arts', 'Commercial'];
    }

    public static function entryNames(School $school, string $variant = 'default'): array
    {
        $kind = $school->kind ?? 'school';
        if ($kind === 'ngo') {
            return ['Cohort 1'];
        }
        if ($kind === 'training') {
            return ['Beginner'];
        }

        return match ($variant) {
            'primary' => ['Creche', 'Nursery 1', 'Primary 1'],
            'both' => ['Creche', 'Nursery 1', 'Primary 1', 'JSS1', 'SS1'],
            default => ['JSS1', 'SS1'],
        };
    }

    public static function variants(School $school): array
    {
        if (($school->kind ?? 'school') !== 'school') {
            return ['default' => ($school->kind === 'ngo' ? 'Cohorts 1–3' : 'Beginner / Intermediate / Advanced')];
        }

        return [
            'default' => 'Nigerian secondary (JSS1–SS3)',
            'primary' => 'Primary (Creche–Primary 6)',
            'both' => 'Primary plus secondary',
        ];
    }
}
