<?php

use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;
use App\User;
use App\Brand;
use App\Category;
use App\Color;
use App\Condition;
use App\Status;

$factory->define(App\Product::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence,
        'description' => $faker->paragraph,
        'dimensions' => $faker->sentence,
        'original_price' => $faker->randomFloat(2),
        'price' => $faker->randomFloat(2),
        'commission' => $faker->randomNumber(2),
        'user_id' => User::all()->random()->id,
        'brand_id' => Brand::all()->random()->id,
        'category_id' => Category::whereNotNull('parent_id')->get()->random()->id,
        'condition_id' => Condition::all()->random()->id,
        'status_id' => Status::all()->random()->id,
        'images' => [UploadedFile::fake()->image('image'), UploadedFile::fake()->image('image'),],
    ];
});

$factory->state(App\Product::class, 'with_real_images', function ($faker) {
    return [
        'images' => [new UploadedFile($faker->image, 'image'), new UploadedFile($faker->image, 'image'),],
    ];
});
