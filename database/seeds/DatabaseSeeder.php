<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;


class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        // $this->call(UserTableSeeder::class);

        $generator = Faker\Factory::create();

        // Brgy Captains Generator
        for($x=0; $x<5; $x++)
        {
            $brgy_id = DB::table('brgy_captain_candidate')->insertGetId([
                'first_name' => $generator->firstName,
                'middle_name' => $generator->lastName,
                'last_name' => $generator->lastName,
                'address' => $generator->address,
                'updated_at' => new DateTime(),
                'created_at' => new DateTime(),
            ]);


            // Constituents Generator
            for($y=0; $y<5; $y++)
            {

                $has_unpaid_taxes = ($y == 3 ? true:false);
                $has_criminal_record = ($y == 2 ? true:false);

                $constituent_id = DB::table('constituent')->insertGetId([
                    'first_name' => $generator->firstName,
                    'middle_name' => $generator->lastName,
                    'last_name' => $generator->lastName,
                    'address' => '8 Fontaine Ext. East Tapinac Olongapo City',
                    'has_unpaid_tax' => $has_unpaid_taxes,
                    'has_record' => $has_criminal_record,
                    'brgy_captain_id' => $brgy_id,
                    'updated_at' => new DateTime(),
                    'created_at' => new DateTime(),
                ]);
            
                // Taxes Generator
                if ($y == 3)
                {
                    for($a=0; $a<2; $a++)
                    {
                        DB::table('tax')->insert([
                            'constituent_id' => $constituent_id,
                            'amount' => rand(100,10000),
                            'payment_month' => $generator->monthName,
                            'payment_year' => rand(1990,2016),
                            'status' => 'Unpaid',
                            'updated_at' => new DateTime(),
                            'created_at' => new DateTime(),
                        ]);
                    }
                }
            

                // Criminal Record Generator
                if ($y == 2)
                {
                    DB::table('criminal_record')->insert([
                        'constituent_id' => $constituent_id,
                        'case_name' => 'Reckless driving',
                        'details' => 'Killed someone while drunk',
                        'execution_date' => new DateTime(),
                        'updated_at' => new DateTime(),
                        'created_at' => new DateTime(),
                    ]);
                }


            }
        }


        Model::reguard();
    }
}
