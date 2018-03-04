<?php

/**
 * Per Str::slug.
 */
const SLUG_REGEX = '[-\pL\pN\s]+';
const ID_REGEX = '[0-9]+';

function create_routes($model, $regex)
{
    $plural = str_plural($model);
    $snakes = snake_case($plural);
    $snake = snake_case($model);

    Route::get($snakes, $model . 'Controller@index')->name($snakes);
    Route::get($snakes . '/{' . $snake . '}', $model. 'Controller@show')->name($snake . '.get')->where($snake, $regex);

    Route::group(['middleware' => ['auth:api', 'role:admin']], function () use ($model, $regex, $plural, $snakes, $snake) {
        Route::post($snakes, $model . 'Controller@store')->name($snake . '.create');
        Route::patch($snakes . '/{' . $snake . '}', $model . 'Controller@update')
            ->name($snake . '.update')->where($snake, SLUG_REGEX);
    });
}
