<?php

/* *created by Niha Siddiqui 2022-09-02
    * Company registration controller methods
*/

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Users;
use App\Models\Company;
use App\Helpers\ResponseHandler;
use File;
use Validator;
use DateTime;

class UserAPIController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $data = Users::userList($request);
            return ResponseHandler::success($data);
        } catch (Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        if (empty($id) || $id == '' || $id == null) {
            return ResponseHandler::validationError(['employee id is required.']);
        }

        $data = Users::with('company', 'roles')->find($id);
        if (empty($data)) {
            return ResponseHandler::validationError(['User with this id not found.']);
        }
        return ResponseHandler::success($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request, $id)
    {
        $user = Users::find($id);
        try {
            if (Users::where('email', '=', $request->email)->where('id', '!=', $id)->exists()) {
                return ResponseHandler::validationError(['User with this email address already exists.']);
            }

            $input = $request->all();
            $response = Users::updateUser($input, $user);
            return ResponseHandler::success($response, 'updated successfully');
        } catch (\Exception $e) {

            return ResponseHandler::serverError($e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        try {
            if (Users::whereId($id)->exists()) {
                $user_status = Users::deleteUsers($id);
                return ResponseHandler::success(['User deleted successfully']);
            } else {
                return ResponseHandler::validationError(['User not found']);
            }
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }
}
