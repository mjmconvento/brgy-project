<?php

Route::get('/', function () {
    return view('index');
});

// Constituents Routes
Route::get('/constituents', 'ConstituentController@index');
Route::get('/constituent/create', 'ConstituentController@create');
Route::post('/constituent/create', 'ConstituentController@store');
Route::get('/constituent/{id}', 'ConstituentController@edit');
Route::get('/constituent/show/{id}', 'ConstituentController@show');
Route::put('/constituent/{id}', 'ConstituentController@update');
Route::delete('/constituent/delete/{id}', 'ConstituentController@destroy');


// Route::get('/constituent/{id}', 'ConstituentController@show');

