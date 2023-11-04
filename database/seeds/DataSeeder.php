<?php

use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($i = 1; $i < 500; $i++) {
            factory(App\Models\Stock::class, 1)->create();
        }
    }
}
