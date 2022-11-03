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


Route::get('/test', 'CompanyAPIController@getCompanyWithEmployeesCount');
Route::get('/invitations', 'InvitationController@getAllInvites');


Route::post('register', [RegisterController::class, 'register']);
Route::post('login', [RegisterController::class, 'login'])->name('login');


Route::group(['middleware' => ['auth:api', 'role:super-admin|pro-user|standard-user']], function () {
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/companies', [CompanyAPIController::class, 'index']);
        Route::post('/companies', [CompanyAPIController::class, 'store']);
        Route::put('/company/{id}', [CompanyAPIController::class, 'update']);
        Route::get('/users/all', 'UserAPIController@index');
        Route::delete('/user/{id}/delete', 'UserAPIController@destroy');
        Route::delete('/company/{id}/delete', 'CompanyAPIController@destroy');
    });
    Route::get('/employees', [EmployeesAPIController::class, 'index']);
    Route::post('/employee/{employee}/edit', 'EmployeesAPIController@update');
    Route::get('/employees/view/{id}', [EmployeesAPIController::class, 'show']);
    Route::post('/employees', 'EmployeesAPIController@store');
    Route::get('/user/{id}', 'UserAPIController@show');
    Route::put('/user/{id}/edit', 'UserAPIController@update');
    Route::delete('/employee/{id}/delete', 'EmployeesAPIController@destroy');
    Route::post('/invitation/send', 'InvitationController@sendInvites');

    // ******** Dashboard Routes *************
    Route::get('/dashboard', 'UserAPIController@getdashboardData')->name('dashboard-get');

    // ******** Positions Routes *************
    Route::get('/positions', 'PositionsController@index')->name('positions-get');

    // ******** Duties Routes *************
    Route::get('/duties', 'DutiesAPIController@index')->name('duties-get');
    Route::post('/duties/create-update', 'DutiesAPIController@createUpdate')->name('duties-create-update');
    Route::delete('employee/{employeeId}/duties/{dutyId}/delete', 'DutiesAPIController@destroy')->name('duties-delete');


    // ******** Employee Certificate Routes *************
    Route::get('/certificates', 'DutiesAPIController@index')->name('cert-get');
    Route::post('/employee/certificate/create', 'EmployeesAPIController@createCertificate')->name('cert-create');
    Route::post('/employee/certificate/update', 'EmployeesAPIController@updateCertificate')->name('cert-update');
    Route::delete('employee/{employeeId}/certificates/{certificateId}/delete', 'EmployeesAPIController@destroyCertificate')->name('cert-delete');

    // ******** Duties Routes *************
    Route::get('/roles', 'UserAPIController@getAllRoles')->name('roles-get');


    // ******** Share Data with other Companies Routes *************
    // Route::post('/share', 'ShareDataController@createDataToShare')->name('shareData-create');
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
