<?php

Route::get('/', function () {
    return view('index');
});

// Constituents Routes
Route::get('/constituents', 'ConstituentController@index');
Route::get('/constituent/show/{id}', 'ConstituentController@show');
Route::get('/constituent/create', 'ConstituentController@create');
Route::post('/constituent/create', 'ConstituentController@store');
Route::get('/constituent/{id}', 'ConstituentController@edit');
Route::put('/constituent/{id}', 'ConstituentController@update');
Route::delete('/constituent/delete/{id}', 'ConstituentController@destroy');

// CriminalRecord Routes
Route::get('/criminal_record/{cons_id}', 'CriminalRecordController@create');
Route::post('/criminal_record/{cons_id}', 'CriminalRecordController@create');