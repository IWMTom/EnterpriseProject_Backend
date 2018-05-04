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

Route::group(['prefix' => 'user'], function()
{
	Route::get('{id}/photo', 'API\UserController@GetProfilePhoto')->where('id', '[0-9]+');
});

Route::group(['middleware' => 'auth:api'], function()
{
	Route::group(['prefix' => 'user'], function()
	{
		Route::post('dashboard', 'API\UserController@GetDashboardData');
		Route::post('{id}', 'API\UserController@GetUserByID')->where('id', '[0-9]+');
		Route::post('details', 'API\UserController@GetDetails');
		Route::post('updatePushToken', 'API\UserController@UpdatePushToken');
		Route::post('updatePassword', 'API\UserController@UpdatePassword');
		Route::post('updateEmail', 'API\UserController@UpdateEmail');
		Route::post('updatePhoneNumber', 'API\UserController@UpdatePhoneNumber');
		Route::post('updateUserInfo', 'API\UserController@UpdateUserInfo');
		Route::post('updateProfilePhoto', 'API\UserController@UpdateProfilePhoto');
	});

	Route::group(['prefix' => 'listing'], function()
	{
		Route::post('new', 'API\ListingController@NewListing');
		Route::post('list', 'API\ListingController@GetListings');
		Route::post('list/radius/{radius}', 'API\ListingController@GetListingsByRadius')->where('radius', '[0-9]+');
		Route::post('list/user', 'API\ListingController@GetListingsByUser');
		Route::post('{id}', 'API\ListingController@FindListing')->where('id', '[0-9]+');
		Route::post('{id}/delete', 'API\ListingController@DeleteListing')->where('id', '[0-9]+');
		Route::post('{id}/bids', 'API\ListingController@GetBids')->where('id', '[0-9]+');
		Route::post('{id}/bids/new', 'API\ListingController@NewBid')->where('id', '[0-9]+');
	});

	Route::group(['prefix' => 'bid'], function()
	{
		Route::post('{id}/details', 'API\ListingController@BidDetails')->where('id', '[0-9]+');
		Route::post('{id}/delete', 'API\ListingController@DeleteBid')->where('id', '[0-9]+');
		Route::post('{id}/accept', 'API\ListingController@AcceptBid')->where('id', '[0-9]+');
	});

	Route::group(['prefix' => 'contract'], function()
	{
		Route::post('list/courier', 'API\ListingController@GetContractsCourier');
		Route::post('list/shipper', 'API\ListingController@GetContractsShipper');
	});
});