<?php

use Illuminate\Database\Seeder;

class MasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\SettlementCompany::class, 10)->create();
        factory(App\Models\Store::class, 50)->create();
        factory(App\Models\Menu::class, 300)->create();
        factory(App\Models\GenreGroup::class, 500)->create();
        factory(App\Models\Image::class, 300)->create();
        factory(App\Models\Story::class, 30)->create();

        for ($i = 0; $i < 300; $i++) {
            factory(App\Models\Price::class, 1)->create();
        }
    }
}
