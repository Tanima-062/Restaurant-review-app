<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Image;
use App\Models\Menu;
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

$factory->define(Image::class, function (Faker $faker) {
    return [
        'image_cd' => config('code.imageCd.menuMain'),
        'url' => $faker->randomElement([
            'https://previews.123rf.com/images/vectorfusionart/vectorfusionart1706/vectorfusionart170605458/81424446-%E3%83%80%E3%83%9F%E3%83%BC%E3%82%AB%E3%83%BC%E3%83%89%E9%A3%9F%E5%93%81%E9%9D%92%E3%81%84%E6%9C%A8%E8%A3%BD%E6%9C%BA%E3%81%AE%E4%B8%8A%E3%81%AE%E3%83%87%E3%82%B8%E3%82%BF%E3%83%AB%E5%90%88%E6%88%90-3-d.jpg',
            'https://www.yamaguchi-ygc.ed.jp/ogori-j/images/topics/R2-09-30_1.jpg',
            'https://grand-mirage.com/tcr/wp-content/uploads/2020/01/31-e1579077008611.jpg',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRGTlRli-emFBm8NQzkpk9uu3eAkEd_va8Clg&usqp=CAU',
            'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT4L3-s--0pGsmj6jBFdErcOWtQinFJe4dSSA&usqp=CAU',
        ]),
        'app_cd' => $faker->randomElement([key(config('code.appCd.to'))]),
        'menu_id' => Menu::all()->random()->id,
        'store_id' => Store::all()->random()->id,
    ];
});
