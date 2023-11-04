<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Space;
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

$factory->define(Space::class, function (Faker $faker) {
    return [
        'date' => $faker->date(),
        'time' => $faker->time(),
        'persons' => $faker->numberBetween(1, 4),
        'smoking' => $faker->numberBetween(0, 1),
        'vacancy' => $faker->numberBetween(1, 10),
        'store_id' => Store::all()->random()->id,
    ];
});
