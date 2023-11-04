<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

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

$factory->define(SettlementCompany::class, function (Faker $faker) {
    return [
        'name' => $faker->company(),
        'postal_code' => $faker->postcode,
        'tel' => $faker->isbn10,
        'address' => $faker->streetAddress,
        'payment_cycle' => $faker->randomElement(collect(config('const.settlement.payment_cycle'))->pluck('value')->all()),
        'result_base_amount' => $faker->randomElement(collect(config('const.settlement.result_base_amount'))->pluck('value')->all()),
        'tax_calculation' => $faker->randomElement(collect(config('const.settlement.tax_calculation'))->pluck('value')->all()),
        'account_type' => $faker->randomElement(collect(config('const.settlement.account_type'))->pluck('value')->all()),
        'bank_name' => $faker->city.'銀行',
        'branch_name' => $faker->city.'支店',
        'branch_number' => $faker->randomNumber(3),
        'account_number' => $faker->bankAccountNumber,
        'billing_email_1' => $faker->email,
        'published' => 1
    ];
});
