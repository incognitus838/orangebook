<?php

Route::get('/dashboard', 'DashboardController@index');
Route::get('/circulars', 'DashboardController@circulars');
Route::get('/child/{id}', 'ChildController@show');
Route::get('/child/{id}/results', 'ChildController@results');
Route::get('/child/{id}/results/pdf', 'ChildController@pdf');
Route::get('/child/{id}/attendance', 'ChildController@attendance');
