<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

// CREATE
Route::post('/new_adunit', 'DfpController@createAdUnit');
//Route::get('/new_adunit', 'DfpController@createAdUnit');
Route::post('/new_placement', 'DfpController@createPlacement');
Route::post('/new_lineitem', 'DfpController@createLineItem');
Route::post('/update_lineitem', 'DfpController@updateLineItem');
Route::post('/new_campaign', 'DfpController@createCampaign');
Route::get('/new_advertiser', 'DfpController@createAdvertiser');

// REPORTS
Route::post('/get_report', 'DfpController@getReport');
Route::post('/get_oauth2code', 'DfpController@getOauth2Code');
Route::post('/get_token', 'DfpController@getToken');

// UPDATE
Route::post('/categorize_sites', 'DfpController@addAdUnitsToPlacement');

// TEST
Route::get('/test', 'HomeController@showWelcome');

// NETWORK
Route::post('/get_user_network', 'DfpController@getUserNetwork');

// ORDERS
Route::post('/get_orders', 'DfpController@getOrders');
Route::post('/target_adunits_lineitem', 'DfpController@excludeAdunitsFromLineItem');

// LINE-ITEMS
Route::post('/get_lineitems', 'DfpController@getLineItems');

//READ-ONLY
Route::post('/get_adunits', 'DfpController@getAdUnits');