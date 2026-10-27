<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SchoolOnboardingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CrestSaaSSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlansTableSeeder::class);

        $platform = User::updateOrCreate(
            ['email' => 'platform@orangebook.ng'],
            [
                'school_id' => null,
                'usergroup_id' => 1,
                'name' => 'Orangebook Platform',
                'mobile_no' => '08030000001',
                'password' => Hash::make('password'),
                'status' => 'active',
                'email_verified' => true,
                'email_verified_at' => now(),
            ]
        );
        Userprofile::updateOrCreate(
            ['user_id' => $platform->id],
            ['usergroup_id' => 1, 'firstname' => 'Orangebook', 'lastname' => 'Platform', 'status' => 'active']
        );

        $pro = Plan::where('name', 'pro')->first();
        $crest = School::where('slug', 'ikeja-secondary')->first()
            ?: School::where('slug', 'crest-secondary')->first()
            ?: School::find(1);
        if ($crest && $pro) {
            $admin = User::where('school_id', $crest->id)->where('usergroup_id', 3)->first();
            Subscription::updateOrCreate(
                ['school_id' => $crest->id],
                [
                    'user_id' => $admin?->id,
                    'plan_id' => $pro->id,
                    'status' => 'active',
                    'end_date' => now()->addYear()->toDateString(),
                ]
            );
            $crest->primary_color = '#1B2A4A';
            $crest->save();
        }

        if (!School::where('slug', 'queens-college-ikeja')->exists()) {
            $trial = Plan::where('name', 'trial')->first();
            app(SchoolOnboardingService::class)->createSchool([
                'school_name' => 'Queens College Ikeja',
                'admin_name' => 'Halima Bello',
                'email' => 'admin@queens.ng',
                'phone' => '08035550909',
                'password' => 'password',
                'slug' => 'queens-college-ikeja',
                'primary_color' => '#7A1F3D',
                'address' => 'Ikeja, Lagos, Nigeria',
            ], $trial);
        }
    }
}
