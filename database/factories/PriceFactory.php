<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Menu;
use App\Models\Price;
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

$factory->define(Price::class, function (Faker $faker) {

    $date = Carbon::today()->format('Y-m-d');
    $endDate = Carbon::today()->addMonth()->format('Y-m-d');
    $menus = Menu::all();
    $menuId =$menus->random()->id;
    echo $menuId.'¥n';
    while (true) {
        try {
            $price = Price::where('start_date', $date)->where('end_date', $endDate)->where('menu_id', $menuId)->exists();
            if ($price) {
                $menuId =$menus->random()->id;
                echo $menuId.'¥n';
            } else {
                break;
            }
            //$date = Carbon::tomorrow();
            //Price::where('date', $date)->where('menu_id', $menuId)->firstOrFail();
        } catch (\Exception $e) {
            break;
        }
    }

    return [
        'price_cd' => $faker->randomElement(array_values(config('code.priceCd'))),
        'price' => $faker->numberBetween(500, 1900),
        'start_date' => $date,
        'end_date' => $endDate,
        'menu_id' => $menuId,
    ];
});
