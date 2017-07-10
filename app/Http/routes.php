<?php


// Authentication routes
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');

// Registration routes
Route::get('auth/register', 'Auth\AuthController@getRegister');
Route::post('auth/register', 'Auth\AuthController@postRegister');

// Routes that neededn authentication
Route::group(['middleware' => 'auth'], function () {
	Route::get('/', 'ConstituentController@index');

	// Constituents Routes
	Route::get('/constituents', 'ConstituentController@index');
	Route::get('/constituent/show/{id}', 'ConstituentController@show');
	Route::get('/constituent/create', 'ConstituentController@create');
	Route::post('/constituent/create', 'ConstituentController@store');
	Route::get('/constituent/{id}', 'ConstituentController@edit');
	Route::put('/constituent/{id}', 'ConstituentController@update');
	Route::delete('/constituent/{id}', 'ConstituentController@destroy');

	// CriminalRecord Routes
	Route::get('/criminal_record/{cons_id}', 'CriminalRecordController@create');
	Route::post('/criminal_record/{cons_id}', 'CriminalRecordController@store');
	Route::get('/criminal_record/{cons_id}/{id}', 'CriminalRecordController@edit');
	Route::put('/criminal_record/{cons_id}/{id}', 'CriminalRecordController@update');
	Route::delete('/criminal_record/{cons_id}', 'CriminalRecordController@destroy');

	// BrgyCaptain Routes
	Route::get('/brgy_captains', 'BrgyCaptainController@index');
	Route::get('/brgy_captain/show/{id}', 'BrgyCaptainController@show');
	Route::get('/brgy_captain/create', 'BrgyCaptainController@create');
	Route::post('/brgy_captain/create', 'BrgyCaptainController@store');
	Route::get('/brgy_captain/{id}', 'BrgyCaptainController@edit');
	Route::put('/brgy_captain/{id}', 'BrgyCaptainController@update');
	Route::delete('/brgy_captain/{id}', 'BrgyCaptainController@destroy');

	// Taxes Routes
	Route::get('/tax/{cons_id}', 'TaxController@create');
	Route::post('/tax/{cons_id}', 'TaxController@store');
	Route::get('/tax/{cons_id}/{id}', 'TaxController@edit');
	Route::put('/tax/{cons_id}/{id}', 'TaxController@update');
	Route::delete('/tax/{cons_id}', 'TaxController@destroy');
});

