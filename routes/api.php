<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\EmployeesAPIController;
use App\Http\Controllers\API\CompanyAPIController;

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

Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login'])->name('login');

 
Route::group(['middleware' => ['auth:api'], 'role:super-admin|pro-admin'], function () {
    Route::group(['prefix' => 'admin'], function () {

        Route::get('/employees', [EmployeesAPIController::class, 'index']);
        Route::get('/employees/view/{id}', [EmployeesAPIController::class, 'show']);
        Route::post('/employees', 'EmployeesAPIController@store');
        Route::get('/companies', [CompanyAPIController::class, 'index']);
        Route::post('/companies', [CompanyAPIController::class, 'store']);
        Route::put('/company/{id}', [CompanyAPIController::class, 'update']);
        Route::get('/users/all', 'UserAPIController@index');
        Route::get('/user/{id}', 'UserAPIController@show');
        Route::post('/user/{user}/edit', 'UserAPIController@update');
        Route::post('/employee/{employee}/edit', 'EmployeesAPIController@update');
        Route::delete('/user/{id}/delete', 'UserAPIController@destroy');
        Route::delete('/employee/{id}/delete', 'EmployeesAPIController@destroy');
        Route::delete('/company/{id}/delete', 'CompanyAPIController@destroy');
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
