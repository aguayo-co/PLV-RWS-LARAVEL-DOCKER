<?php

use Faker\Generator as Faker;
use Illuminate\Http\UploadedFile;
use App\ShippingMethod;

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

$factory->define(App\User::class, function (Faker $faker) {
    return [
        'email' => $faker->unique()->safeEmail,
        'password' => 'secret',
        'first_name' => $faker->firstName,
        'last_name' => $faker->lastName,
    ];
});

$factory->state(App\User::class, 'profile', function ($faker) {
    return [
        'shipping_method_ids' => [ShippingMethod::all()->random()->id],
        'phone' => $faker->phoneNumber,
        'about' => $faker->paragraph,
        'cover' => UploadedFile::fake()->image('cover'),
        'picture' => UploadedFile::fake()->image('picture'),
    ];
});

$factory->state(App\User::class, 'profile_with_real_images', function ($faker) {
    return [
        'phone' => $faker->phoneNumber,
        'about' => $faker->paragraph,
        'cover' => new UploadedFile($faker->image, 'cover'),
        'picture' => new UploadedFile($faker->image, 'picture'),
    ];
});
