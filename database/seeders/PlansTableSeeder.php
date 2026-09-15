<?php

namespace Database\Seeders;

use DB;
use Illuminate\Database\Seeder;

class PlansTableSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'cycle' => 14,
                'name' => 'trial',
                'display_name' => 'Free trial',
                'order' => 1,
                'amount' => 0,
                'no_of_members' => 80,
            ],
            [
                'cycle' => 90,
                'name' => 'basic',
                'display_name' => 'Basic',
                'order' => 2,
                'amount' => 25000,
                'no_of_members' => 400,
            ],
            [
                'cycle' => 365,
                'name' => 'pro',
                'display_name' => 'Pro',
                'order' => 3,
                'amount' => 60000,
                'no_of_members' => 999999,
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('plans')->updateOrInsert(
                ['name' => $plan['name']],
                $plan + [
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
