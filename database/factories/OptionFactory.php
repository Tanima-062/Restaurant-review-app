<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Menu;
use App\Models\Option;
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

$factory->define(Option::class, function (Faker $faker) {
    return [
        'option_cd' => 'option_cd',
        'required' => $faker->numberBetween(0, 1),
        'keyword_id' => 1,
        'keyword' => 'element_name',
        'contents_id' => 1,
        'contents' => 'content',
        'menu_id' => Menu::all()->random()->id,
        'price' => $faker->numberBetween(100, 500),
    ];
});
