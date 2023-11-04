<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Genre;
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

$factory->define(Genre::class, function (Faker $faker) {
    return [
        'name' => 'メニュー名',
        'genre_cd' => $faker->randomElement(array_values(config('code.genreCd'))),
        'app_cd' => $faker->randomElement(array_values(config('code.serviceCd'))),
    ];
});
