<?php

namespace App\Services;

use App\Helpers\SiteHelper;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Section;
use App\Models\Standard;
use App\Models\StandardLink;
use App\Models\StudentAcademic;
use App\Models\Subject;
use App\Models\SubjectCatalog;
use App\Models\Teacherlink;
use App\Support\LevelPresets;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassStructureService
{
    public function addLevel(School $school, string $name, bool $entry = false): Standard
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Give a level name.']);
        }
        if (Standard::where('school_id', $school->id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            throw ValidationException::withMessages(['name' => 'That level already exists.']);
        }
        $order = (int) Standard::where('school_id', $school->id)->max('order') + 1;

        return Standard::create([
            'school_id' => $school->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$school->id.'-'.$order,
            'order' => str_pad((string) $order, 2, '0', STR_PAD_LEFT),
            'status' => 1,
            'is_entry' => $entry,
        ]);
    }

    public function applyPreset(School $school, string $variant = 'default'): int
    {
        $added = 0;
        $entry = LevelPresets::entryNames($school, $variant);
        foreach (LevelPresets::levels($school, $variant) as $name) {
            if (! Standard::where('school_id', $school->id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
                $this->addLevel($school, $name, in_array($name, $entry, true));
                $added++;
            }
        }
        foreach (LevelPresets::arms($school) as $name) {
            if (! Section::where('school_id', $school->id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
                $this->addArm($school, $name);
                $added++;
            }
        }

        return $added;
    }

    public function addArm(School $school, string $name): Section
    {
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Give an arm name.']);
        }
        if (Section::where('school_id', $school->id)->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            throw ValidationException::withMessages(['name' => 'That arm already exists.']);
        }

        return Section::create([
            'school_id' => $school->id,
            'name' => $name,
            'status' => 1,
        ]);
    }

    public function moveLevel(Standard $level, int $dir): void
    {
        $siblings = Standard::where('school_id', $level->school_id)->orderBy('order')->get();
        $ids = $siblings->pluck('id')->all();
        $i = array_search($level->id, $ids, true);
        $j = $i + $dir;
        if ($i === false || $j < 0 || $j >= count($ids)) {
            return;
        }
        $swap = $ids[$j];
        $ids[$j] = $ids[$i];
        $ids[$i] = $swap;
        foreach ($ids as $n => $id) {
            Standard::where('id', $id)->update(['order' => str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT)]);
        }
    }

    public function archiveLevel(Standard $level): void
    {
        $year = SiteHelper::getAcademicYear($level->school_id);
        $classIds = StandardLink::where('school_id', $level->school_id)
            ->where('standard_id', $level->id)
            ->when($year, fn ($q) => $q->where('academic_year_id', $year->id))
            ->pluck('id');
        if ($classIds->isNotEmpty() && StudentAcademic::whereIn('standardLink_id', $classIds)->exists()) {
            throw ValidationException::withMessages(['level' => 'This level has learners this year. Move them first.']);
        }
        $level->status = 0;
        $level->save();
    }

    public function addClasses(School $school, int $standardId, array $sectionIds, ?int $teacherId): array
    {
        $year = SiteHelper::getAcademicYear($school->id);
        $standard = Standard::where('id', $standardId)->where('school_id', $school->id)->firstOrFail();
        $created = [];
        foreach ($sectionIds as $sectionId) {
            $section = Section::where('id', $sectionId)->where('school_id', $school->id)->firstOrFail();
            $exists = StandardLink::where('school_id', $school->id)
                ->where('academic_year_id', $year->id)
                ->where('standard_id', $standard->id)
                ->where('section_id', $section->id)
                ->exists();
            if ($exists) {
                continue;
            }
            $created[] = StandardLink::create([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $standard->id,
                'section_id' => $section->id,
                'class_teacher_id' => $teacherId,
                'status' => 1,
            ]);
        }
        if ($created === []) {
            throw ValidationException::withMessages(['section_ids' => 'Those classes already exist.']);
        }

        return $created;
    }

    public function copyFromLastYear(School $school): int
    {
        $year = SiteHelper::getAcademicYear($school->id);
        $prev = AcademicYear::where('school_id', $school->id)
            ->where('id', '!=', $year->id)
            ->orderByDesc('start_date')
            ->first();
        if (! $prev) {
            throw ValidationException::withMessages(['year' => 'There is no previous year to copy.']);
        }
        $copied = 0;
        $old = StandardLink::where('school_id', $school->id)->where('academic_year_id', $prev->id)->get();
        foreach ($old as $class) {
            $new = StandardLink::firstOrCreate([
                'school_id' => $school->id,
                'academic_year_id' => $year->id,
                'standard_id' => $class->standard_id,
                'section_id' => $class->section_id,
            ], [
                'class_teacher_id' => $class->class_teacher_id,
                'status' => 1,
            ]);
            $links = Teacherlink::where('standardLink_id', $class->id)->get();
            foreach ($links as $link) {
                $subject = Subject::find($link->subject_id);
                if (! $subject) {
                    continue;
                }
                $copy = Subject::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $new->standard_id,
                    'section_id' => $new->section_id,
                    'name' => $subject->getRawOriginal('name') ?? $subject->name,
                ], [
                    'code' => $subject->code,
                    'type' => $subject->type,
                    'status' => 1,
                ]);
                Teacherlink::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standardLink_id' => $new->id,
                    'subject_id' => $copy->id,
                ], [
                    'teacher_id' => $link->teacher_id,
                ]);
                $copied++;
            }
            if ($links->isEmpty()) {
                $copied++;
            }
        }

        return $copied;
    }

    public function addCatalogSubject(School $school, string $name, ?string $code, string $type = 'core'): SubjectCatalog
    {
        $year = SiteHelper::getAcademicYear($school->id);
        $name = trim($name);
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Give a subject name.']);
        }
        $exists = SubjectCatalog::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($name)])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['name' => 'That subject is already in the list.']);
        }

        return SubjectCatalog::create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
            'name' => $name,
            'code' => $code ?: strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name).'SUB', 0, 8)),
            'type' => in_array($type, ['core', 'elective'], true) ? $type : 'core',
            'status' => 1,
        ]);
    }

    public function applySubjects(School $school, array $catalogIds, array $classIds, ?int $teacherId = null): int
    {
        $year = SiteHelper::getAcademicYear($school->id);
        $catalog = SubjectCatalog::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereIn('id', $catalogIds)
            ->get();
        $classes = StandardLink::where('school_id', $school->id)
            ->where('academic_year_id', $year->id)
            ->whereIn('id', $classIds)
            ->get();
        $made = 0;
        foreach ($classes as $class) {
            foreach ($catalog as $item) {
                $subject = Subject::firstOrCreate([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standard_id' => $class->standard_id,
                    'section_id' => $class->section_id,
                    'name' => $item->name,
                ], [
                    'code' => $item->code,
                    'type' => $item->type,
                    'status' => 1,
                ]);
                $exists = Teacherlink::where('standardLink_id', $class->id)->where('subject_id', $subject->id)->exists();
                if ($exists) {
                    continue;
                }
                Teacherlink::create([
                    'school_id' => $school->id,
                    'academic_year_id' => $year->id,
                    'standardLink_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacherId,
                ]);
                $made++;
            }
        }

        return $made;
    }
}
