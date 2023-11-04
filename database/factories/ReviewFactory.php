<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Favorite;
use App\Models\Image;
use App\Models\Menu;
use App\Models\Reservation;
use App\Models\Review;
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

$factory->define(Review::class, function (Faker $faker) {
    return [
        'published' => 1,
        'user_name' => $faker->name(),
        'user_id' => Favorite::all()->random()->user_id,
        'body' => 'good',
        'evaluation_cd' => $faker->randomElement(array_values(config('code.evaluationCd'))),
        'reservation_id' => Reservation::all()->random()->id,
        'menu_id' => 1, //Menu::all()->random()->id,
        'image_id' => Image::all()->random()->id,
    ];
});
