<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\CommissionRate;
use App\Models\SettlementCompany;
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

$factory->define(CommissionRate::class, function (Faker $faker) {
    return [
        'settlement_company_id' => SettlementCompany::all()->random()->id,
    ];
});
