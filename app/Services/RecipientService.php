<?php

namespace App\Services;

use App\Models\PeopleGroup;
use App\Models\StudentAcademic;
use App\Models\StudentParentLink;
use App\Models\User;

class RecipientService
{
    public const ROLE_GROUPS = [
        'teachers' => [5],
        'students' => [6],
        'parents' => [7],
        'nonteaching' => [8, 10, 11, 13],
        'staff' => [5, 8, 10, 11, 13],
    ];

    public function fromRequest(\Illuminate\Http\Request $request, int $schoolId)
    {
        return $this->resolve($schoolId, [
            'everyone' => $request->boolean('everyone'),
            'groups' => array_values((array) $request->input('groups', [])),
            'group_ids' => array_values(array_filter((array) $request->input('group_ids', []))),
            'class_id' => $request->input('class_id') ? (int) $request->input('class_id') : null,
            'class_who' => $request->input('class_who', 'parents'),
            'user_ids' => array_values(array_filter((array) $request->input('user_ids', []))),
        ]);
    }

    public function resolve(int $schoolId, array $input)
    {
        $ids = collect();
        $base = User::where('school_id', $schoolId)->where('status', 'active');

        if (!empty($input['everyone'])) {
            return (clone $base)->whereNotNull('email')->orderBy('name')->get();
        }

        foreach ((array) ($input['groups'] ?? []) as $group) {
            if (isset(self::ROLE_GROUPS[$group])) {
                $ids = $ids->merge(
                    (clone $base)->whereIn('usergroup_id', self::ROLE_GROUPS[$group])->pluck('id')
                );
            }
        }

        foreach ((array) ($input['group_ids'] ?? []) as $gid) {
            $group = PeopleGroup::where('id', $gid)->where('school_id', $schoolId)->first();
            if ($group) {
                $ids = $ids->merge($group->members()->pluck('users.id'));
            }
        }

        $classId = $input['class_id'] ?? null;
        if ($classId) {
            $studentIds = StudentAcademic::where('school_id', $schoolId)
                ->where('standardLink_id', $classId)
                ->pluck('user_id');
            $who = $input['class_who'] ?? 'parents';
            if (in_array($who, ['students', 'both'], true)) {
                $ids = $ids->merge($studentIds);
            }
            if (in_array($who, ['parents', 'both'], true)) {
                $ids = $ids->merge(
                    StudentParentLink::where('school_id', $schoolId)
                        ->whereIn('student_id', $studentIds)
                        ->pluck('parent_id')
                );
            }
        }

        foreach ((array) ($input['user_ids'] ?? []) as $uid) {
            $ids->push((int) $uid);
        }

        $ids = $ids->unique()->filter()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        return User::where('school_id', $schoolId)
            ->where('status', 'active')
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();
    }

    public function payloadFromRequest(\Illuminate\Http\Request $request): array
    {
        return [
            'everyone' => $request->boolean('everyone'),
            'groups' => array_values((array) $request->input('groups', [])),
            'group_ids' => array_map('intval', array_values(array_filter((array) $request->input('group_ids', [])))),
            'class_id' => $request->input('class_id') ? (int) $request->input('class_id') : null,
            'class_who' => $request->input('class_who', 'parents'),
            'user_ids' => array_map('intval', array_values(array_filter((array) $request->input('user_ids', [])))),
        ];
    }

    public function viewerSees(array $audience, User $viewer): bool
    {
        if (empty($audience) || !empty($audience['everyone'])) {
            return true;
        }
        $role = match ((int) $viewer->usergroup_id) {
            5 => 'teachers',
            6 => 'students',
            7 => 'parents',
            8, 10, 11, 13 => 'nonteaching',
            default => null,
        };
        $groups = (array) ($audience['groups'] ?? []);
        if ($role && in_array($role, $groups, true)) {
            return true;
        }
        if ($role === 'teachers' && in_array('staff', $groups, true)) {
            return true;
        }
        if (in_array($viewer->id, (array) ($audience['user_ids'] ?? []), true)) {
            return true;
        }
        $classId = $audience['class_id'] ?? null;
        if ($classId) {
            $who = $audience['class_who'] ?? 'parents';
            if ($viewer->usergroup_id == 6 && in_array($who, ['students', 'both'], true)
                && (int) optional($viewer->studentAcademicLatest)->standardLink_id === (int) $classId) {
                return true;
            }
            if ($viewer->usergroup_id == 7 && in_array($who, ['parents', 'both'], true)) {
                $childIds = $viewer->childUsers->pluck('id');
                $inClass = StudentAcademic::whereIn('user_id', $childIds)->where('standardLink_id', $classId)->exists();
                if ($inClass) {
                    return true;
                }
            }
        }
        foreach ((array) ($audience['group_ids'] ?? []) as $gid) {
            if (PeopleGroup::where('id', $gid)->whereHas('members', fn ($q) => $q->where('users.id', $viewer->id))->exists()) {
                return true;
            }
        }
        return false;
    }
}
