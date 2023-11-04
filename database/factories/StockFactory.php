<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Menu;
use App\Models\Stock;
use Faker\Generator as Faker;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Stock::class, function (Faker $faker) {
    $dt = new Carbon;
    $date = $dt->today()->format('Y-m-d');
    $menuId = 0;
    $count = 0;
    while (true) {
        try {
            $menuId = Menu::all()->random()->id;
            $stock = Stock::where('date', $date)->where('menu_id', $menuId)->exists();
            if ($stock) {
                $count++;
                $date = $dt->addDay()->format('Y-m-d');
            } else {
                break;
            }
        } catch (\Exception $e) {
            break;
        }
    }

    //echo $count;
    return [
        'stock_number' => $faker->numberBetween(0, 20),
        'date' => $date,
        'menu_id' => $menuId,
    ];
});
