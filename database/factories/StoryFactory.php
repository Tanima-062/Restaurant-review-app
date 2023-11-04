<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Image;
use App\Models\Story;
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

$factory->define(Story::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'app_cd' => 'TO',
        'guide_url' => 'https://jp.skyticket.jp/guide/story/436746',
        'public_flg' => $faker->numberBetween(0, 1),
        'image_id' => Image::all()->random()->id,
    ];
});
