<?php

use Faker\Generator as Faker;

$factory->define(App\Banner::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'title' => $faker->sentence,
        'subtitle' => $faker->sentence(5),
        'button_text' => $faker->sentence(2),
        'url' => $faker->url,
        'image' => $faker->image,
    ];
});
