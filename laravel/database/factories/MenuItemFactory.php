<?php

use Faker\Generator as Faker;
use App\Menu;
use App\MenuItem;

$factory->define(MenuItem::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'url' => $faker->url,
        'icon' => $faker->url,
        'menu_id' => Menu::all()->random()->id,
        'parent_id' => null,
    ];
});

$factory->state(MenuItem::class, 'children', function () {
    return [
        'menu_id' => null,
        'parent_id' => MenuItem::all()->random()->id,
    ];
});
