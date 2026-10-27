<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NigerianLocationsSeeder extends Seeder
{
    public function run(): void
    {
        $countryId = DB::table('countries')->where('short_name', 'NG')->value('id');
        if (!$countryId) {
            $countryId = DB::table('countries')->insertGetId([
                'name' => 'Nigeria',
                'short_name' => 'NG',
                'iso_code' => 'NG',
                'tel_prefix' => '+234',
                'status' => 1,
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $lagos = DB::table('states')->where('country_id', $countryId)->where('name', 'Lagos')->value('id');
        if (!$lagos) {
            $lagos = DB::table('states')->insertGetId([
                'country_id' => $countryId,
                'name' => 'Lagos',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (['Ikeja', 'Lagos Island', 'Surulere', 'Yaba', 'Lekki', 'Agege', 'Ikorodu'] as $city) {
            $exists = DB::table('cities')->where('state_id', $lagos)->where('name', $city)->exists();
            if (!$exists) {
                DB::table('cities')->insert([
                    'country_id' => $countryId,
                    'state_id' => $lagos,
                    'name' => $city,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('schools')->update([
            'country_id' => $countryId,
            'state_id' => $lagos,
        ]);
        $ikeja = DB::table('cities')->where('state_id', $lagos)->where('name', 'Ikeja')->value('id');
        if ($ikeja) {
            DB::table('schools')->update(['city_id' => $ikeja]);
        }
        DB::table('userprofiles')->where('country_id', 7)->update(['country_id' => $countryId, 'state_id' => $lagos]);
    }
}
