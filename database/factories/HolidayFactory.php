<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Holiday;
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

$factory->define(Holiday::class, function (Faker $faker) {
    while (true) {
        try {
            $date = $faker->date();
            Holiday::where('date', $date)->firstOrFail();
        } catch (\Exception $e) {
            break;
        }
    }

    return [
        'name' => $faker->asciify('********************'),
        'date' => $date,
    ];
});
