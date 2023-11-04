<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Area;
use App\Models\SettlementCompany;
use App\Models\Station;
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

$factory->define(Store::class, function (Faker $faker) {
    return [
        'name' => $faker->name(),
        'alias_name' => $faker->slug,
        'code' => $faker->unique()->word,
        'address_1' => $faker->country(),
        'address_2' => $faker->city(),
        'address_3' => $faker->streetAddress,
        'postal_code' => $faker->postcode(),
        'tel' => $faker->phoneNumber(),
        'mobile_phone' => $faker->phoneNumber(),
        'latitude' => floatval(rand(357137, 356916) / 10000), //$faker->latitude(),
        'longitude' => floatval(rand(1397772, 1397708) / 10000), //$faker->longitude(),
        'email_1' => $faker->email(),
        'email_2' => $faker->email(),
        'email_3' => $faker->email(),
        'daytime_budget_lower_limit' => $faker->numberBetween(1500, 3000),
        'daytime_budget_limit' => $faker->numberBetween(3001, 5000),
        'access' => 'access',
        'account' => 'account',
        'pick_up_time_interval' => $faker->randomElement(array_values(config('const.store.pick_up_time_interval'))),
        'order_close_time' => $faker->randomElement(config('const.store.order_close_time')),
        'remarks' => 'remarks',
        'description' => 'description',
        'fax' => $faker->phoneNumber(),
        'use_fax' => 0,
        'reservation_necessity' => 'reservation_necessity',
        'regular_holiday' => $faker->randomElement(array_keys(config('const.store.regular_holiday'))),
        'night_budget_lower_limit' => $faker->numberBetween(1500, 3000),
        'night_budget_limit' => $faker->numberBetween(3001, 5000),
        'can_card' => $faker->numberBetween(0, 1),
        'card_types' => $faker->randomElement(array_keys(config('const.store.card_types'))),
        'can_digital_money' => $faker->numberBetween(0, 1),
        'digital_money_types' => $faker->randomElement(array_keys(config('const.store.digital_money_types'))),
        'has_private_room' => $faker->numberBetween(0, 1),
        'private_room_types' => $faker->randomElement(array_keys(config('const.store.private_room_types'))),
        'has_parking' => $faker->numberBetween(0, 1),
        'has_coin_parking' => $faker->numberBetween(0, 1),
        'number_of_seats' => 'number_of_seats',
        'can_charter' => $faker->numberBetween(0, 1),
        'charter_types' => 'charter_types',
        'smoking' => $faker->numberBetween(0, 1),
        'smoking_types' => $faker->randomElement(array_keys(config('const.store.smoking_types'))),
        'settlement_company_id' => SettlementCompany::all()->random()->id,
        'station_id' => Station::where('prefecture_id', 13)->get()->random()->id,
        'area_id' => Area::all()->random()->id,
    ];
});
