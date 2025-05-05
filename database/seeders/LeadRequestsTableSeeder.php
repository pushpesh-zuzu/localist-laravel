<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class LeadRequestsTableSeeder extends Seeder
{
    public function run()
    {
        DB::disableQueryLog();

        $faker = Faker::create();

        $batchSize = 2000;
        $total = 1000000; // 10 lakhs
        $insertData = [];

        for ($i = 1; $i <= $total; $i++) {
            $insertData[] = [
                'customer_id' => rand(600, 635),
                'service_id' => 33,
                'city' => $faker->city,
                'postcode' => $faker->postcode,
                'questions' => '[{"ques":"What type of business is this for?","ans":"Personal project"}]',
                'phone' => $faker->phoneNumber,
                'details' => $faker->paragraph,
                'images' => null,
                'buyer_id' => rand(1, 5000),
                'recevive_online' => rand(0, 1),
                'professional_letin' => rand(0, 1),
                'credit_score' => $faker->numberBetween(10, 50),
                'is_urgent' => rand(0, 1),
                'is_high_hiring' => rand(0, 1),
                'is_phone_verified' => rand(0, 1),
                'has_additional_details' => rand(0, 1),
                'is_frequent_user' => rand(0, 1),
                'is_updated' => rand(0, 1),
                'status' => $faker->randomElement(['pending', 'approved', 'rejected']),
                'is_read' => rand(0, 1),
                'is_buyer_read' => rand(0, 1),
                'closed_status' => rand(0, 1),
                'should_autobid' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null
            ];

            // Insert in batches to avoid memory overload
            if ($i % $batchSize == 0) {
                DB::table('lead_requests')->insert($insertData);
                $insertData = [];
                echo "Inserted: $i records\n";
            }
        }

        // Insert remaining data
        if (!empty($insertData)) {
            DB::table('lead_requests')->insert($insertData);
            echo "Inserted remaining records\n";
        }
    }
}
