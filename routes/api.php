<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\EmployeesAPIController;

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

 
Route::group(['middleware' => ['auth:api'], 'role:super_admin|pro_admin'], function () {
    Route::group(['prefix' => 'admin'], function () {

        Route::get('/users', [RegisterController::class, 'getAllUsers']);
        Route::get('/employees', [EmployeesAPIController::class, 'index']);
        Route::get('/employees/view/{id}', [EmployeesAPIController::class, 'show']);
        Route::post('/employees', 'EmployeesAPIController@store');
        
    });
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
