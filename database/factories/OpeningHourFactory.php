<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\OpeningHour;
use App\Models\Store;
use Faker\Generator as Faker;

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

$factory->define(OpeningHour::class, function (Faker $faker) {
    return [
        'name' => '通常営業時間',
        'start_at' => $faker->time(),
        'end_at' => $faker->time(),
        'opening_hour_cd' => $faker->randomElement(array_values(config('code.openingHourCd'))),
        'store_id' => Store::all()->random()->id,
    ];
});
