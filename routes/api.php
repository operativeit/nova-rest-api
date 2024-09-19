<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use EomPlus\NovaRestApi\Http\MiddleWare\JwtVerify;


/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. They are protected
| by your tool's "Authorize" middleware by default. Now, go build!
|
*/

Route::group([
    'middleware' => 'api',
    'namespace' => 'EomPlus\NovaRestApi\Http\Controllers',
], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('login', 'AuthController@login');
        Route::post('register', 'AuthController@register');
        Route::post('verify', 'AuthController@verify');

        Route::post('request-password-change', 'AuthController@requestPasswordChange');
        Route::post('password-change', 'AuthController@passwordChange');

        Route::group(['middleware' => JwtVerify::class ], function () {
            Route::get('logout', 'AuthController@logout');
            Route::get('me', 'AuthController@me');
            Route::get('refresh', 'AuthController@refresh');
        });
    });

    Route::group([
        'prefix' => 'v1'
        'middleware' => JwtVerify::class
    ], function () {
        //Route::apiResource('tenants', 'TenantController');
        //Route::apiResource('plans', 'PlanController');
        //Route::apiResource('subscriptions', 'PlanSubscriptionController');
        //Route::apiResource('domains', 'DomainController');
        //Route::apiResource('tenants.domains', 'DomainController');
        //Route::apiResource('tenants.subscriptions', 'PlanSubscriptionController');
    });
});
