<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('login', 'API\UserController@DoLogin');
Route::post('register', 'API\UserController@DoRegister');

Route::group(['middleware' => 'auth:api'], function()
{
	Route::group(['prefix' => 'user'], function()
	{
		Route::post('details', 'API\UserController@GetDetails');
	});

	Route::group(['prefix' => 'listing'], function()
	{
		Route::post('new', 'API\ListingController@NewListing');
		Route::post('list', 'API\ListingController@GetListings');
		Route::post('{id}/bids', 'API\ListingController@GetBids')->where('id', '[0-9]+');
		Route::post('{id}/delete', 'API\ListingController@DeleteListing')->where('id', '[0-9]+');
	});
});