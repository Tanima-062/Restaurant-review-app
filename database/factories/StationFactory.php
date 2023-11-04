<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Area;
use App\Models\Station;
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

$factory->define(Station::class, function (Faker $faker) {
    return [
        'name' => $faker->city(),
        'latitude' => $faker->latitude(),
        'longitude' => $faker->longitude(),
        'area_id' => Area::all()->random()->id,
    ];
});
