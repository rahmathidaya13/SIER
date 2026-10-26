<?php

namespace Database\Seeders;

use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use App\Models\CustomerModel;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create("id_ID");
        $user = User::where('email', 'santimedan12@gmail.com')->first();

        $customers = [];
        $businessTypes = ['retail', 'fnb', 'service', 'manufacture', 'other'];
        $customerTypes = ['individual', 'company', 'government'];
        $customerTags = ['new', 'repeat', 'has_cancelled'];
        $status = ['active', 'inactive', 'blacklisted'];

        for ($i = 0; $i < 50; $i++) {
            $phoneNumber = '08' . $faker->unique()->numerify('##########');
            $customers[] = [
                'id_customer' => Str::uuid(),
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'created_of_branch' => $user->id_branch,
                'name' => $faker->company(),
                'pic_name' => $faker->name(),
                'email' => $faker->email(),
                'phone' => substr($phoneNumber, 0, 13),
                'address' => $faker->address(),
                'customer_type' => $customerTypes[array_rand($customerTypes)],
                'business_type' => $businessTypes[array_rand($businessTypes)],
                'customer_tag' => $customerTags[array_rand($customerTags)],
                'is_member' => $faker->boolean(),
                'status' => $status[array_rand($status)],
                'city' => $faker->city(),
                'tax_number' => Str::random(10),
                'customer_code' => CustomerModel::generateUniqueCode(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        CustomerModel::insert($customers);
    }
}
