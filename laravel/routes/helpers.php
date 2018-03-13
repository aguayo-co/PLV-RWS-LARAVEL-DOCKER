<?php

/**
 * Regex taken from Str::slug.
 */
const SLUG_REGEX = '[-\pL\pN\s]+';
const ID_REGEX = '[0-9]+';

function expand_names($model)
{
    $plural = str_plural($model);
    return [
        'plural' => $plural,
        'snakes' => snake_case($plural),
        'snake' => snake_case($model),
    ];
}

function create_get_routes($model, $regex, $snakes, $snake)
{
    Route::get($snakes, $model . 'Controller@index')->name($snakes);
    Route::get($snakes . '/{' . $snake . '}', $model. 'Controller@show')->name($snake . '.get')->where($snake, $regex);
}

function create_admin_routes($model, $regex, $snakes, $snake, $plural)
{
    Route::post($snakes, $model . 'Controller@store')->name($snake . '.create');
    Route::patch($snakes . '/{' . $snake . '}', $model . 'Controller@update')
        ->name($snake . '.update')->where($snake, SLUG_REGEX);
    Route::delete($snakes . '/{' . $snake . '}', $model . 'Controller@delete')
        ->name($snake . '.delete')->where($snake, SLUG_REGEX);
}

/**
 * Create CRUD routes that have:
 * - Public GET access.
 * - Protected POST, PATCH and DELETE access.
 */
function create_crud_routes($model, $regex)
{
    extract(expand_names($model));

    create_get_routes($model, $regex, $snakes, $snake);
    Route::group(['middleware' => ['auth:api']], function () use ($model, $regex, $snakes, $snake, $plural) {
        create_admin_routes($model, $regex, $snakes, $snake, $plural);
    });
}

/**
 * Create CRUD routes that have:
 * - Protected GET.
 * - Protected POST, PATCH and DELETE access.
 */
function create_protected_crud_routes($model, $regex)
{
    extract(expand_names($model));

    Route::group(['middleware' => ['auth:api']], function () use ($model, $regex, $snakes, $snake, $plural) {
        create_get_routes($model, $regex, $snakes, $snake);
        create_admin_routes($model, $regex, $snakes, $snake, $plural);
    });
}
