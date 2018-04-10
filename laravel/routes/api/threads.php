<?php

Route::get('', ['as' => 'threads', 'uses' => 'ThreadController@index']);
Route::post('', ['as' => 'thread.store', 'uses' => 'ThreadController@store']);
Route::get('{thread}', ['as' => 'thread.show', 'uses' => 'ThreadController@show']);
Route::post('{thread}/messages', ['as' => 'thread.message.create', 'uses' => 'MessageController@store']);
