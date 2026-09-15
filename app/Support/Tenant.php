<?php

namespace App\Support;

use App\Models\School;

class Tenant
{
    public static function resolve(?string $host = null, ?string $querySlug = null): ?School
    {
        if ($querySlug) {
            return School::where('slug', $querySlug)->where('status', 1)->first();
        }

        $host = $host ?: request()->getHost();
        $host = strtolower($host);

        $byDomain = School::where('custom_domain', $host)->where('status', 1)->first();
        if ($byDomain) {
            return $byDomain;
        }

        $base = strtolower((string) config('app.platform_domain', 'localhost'));
        if ($host === $base || $host === '127.0.0.1' || $host === 'localhost') {
            return null;
        }

        $suffix = '.' . $base;
        if (str_ends_with($host, $suffix)) {
            $slug = substr($host, 0, -strlen($suffix));
            if ($slug && $slug !== 'www') {
                return School::where('slug', $slug)->where('status', 1)->first();
            }
        }

        return null;
    }

    public static function get(): ?School
    {
        return app()->bound('currentSchool') ? app('currentSchool') : null;
    }

    public static function set(?School $school): void
    {
        app()->instance('currentSchool', $school);
    }
}
