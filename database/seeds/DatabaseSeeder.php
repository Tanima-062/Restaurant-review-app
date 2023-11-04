<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        factory(App\Models\SettlementCompany::class, 100)->create();
        factory(App\Models\Station::class, 100)->create();
        factory(App\Models\Store::class, 100)->create();
        factory(App\Models\Area::class, 100)->create();
        factory(App\Models\Review::class, 1000)->create();
        factory(App\Models\CommissionRate::class, 100)->create();
        factory(App\Models\Space::class, 100)->create();
        factory(App\Models\Genre::class, 100)->create();
        factory(App\Models\GenreGroup::class, 100)->create();
        factory(App\Models\Menu::class, 100)->create();
        factory(App\Models\Option::class, 100)->create();
        factory(App\Models\Image::class, 100)->create();
        factory(App\Models\Story::class, 100)->create();
        factory(App\Models\Price::class, 100)->create();
        factory(App\Models\Stock::class, 100)->create();
        factory(App\Models\OpeningHour::class, 100)->create();
        factory(App\Models\Holiday::class, 100)->create();
    }
}
