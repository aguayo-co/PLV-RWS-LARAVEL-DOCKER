<?php

use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;

$factory->define(App\Slider::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(2),
        'large_text' => $faker->sentence(5),
        'small_text' => $faker->sentence(4),
        'button_text' => $faker->sentence(2),
        'url' => $faker->url,
        'image' => UploadedFile::fake()->image('image'),
        'orientation' => $faker->randomElement(['left', 'right']),
        'font_color' => $faker->randomElement(['white', 'black']),
    ];
});

$factory->state(App\Slider::class, 'with_real_image', function ($faker) {
    return [
        'image' => new UploadedFile($faker->image, 'image'),
    ];
});
