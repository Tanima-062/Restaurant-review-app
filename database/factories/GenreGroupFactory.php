<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Genre;
use App\Models\GenreGroup;
use App\Models\Menu;
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

$factory->define(GenreGroup::class, function (Faker $faker) {
    return [
        'genre_id' => Genre::all()->random()->id,
        'menu_id' => Menu::all()->random()->id,
        //'service_cd' => $faker->randomElement(array_values(config('code.serviceCd'))),
    ];
});
