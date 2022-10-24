<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Users;
use App\Helpers\ResponseHandler;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Auth;
use App\Models\Roles;
use App\Models\Company;
use Hash;
use DB;
use Validator;

class RegisterController extends BaseController
{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'contact_username' => 'required',
            // 'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'date_of_birth' => 'date_format:Y-m-d',
            'password' => 'required|min:6|regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            // 'c_password' => 'required|same:password',
            'role' => 'required',
            'company_name' => 'required',
            'business_type' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        try {
        DB::beginTransaction();

        $input = $request->all();

        $companyInputData = [
            'company_name' => $input['company_name'],
            'company_ceo_name' => $input['company_ceo_name'],
            'business_type' => $input['business_type'],
            'name' => $input['contact_username'],
            'email' => $input['email'],
            'password' => $input['password'],
            'is_active' => true,
            'status' => true,
        ];

       
        $company = Company::where('company_name', $input['company_name'])->where('business_type', $input['business_type'])->first();

        if (!isset($company) && empty($company)) {

            $company = Company::create($companyInputData);
        }

        if (isset($company) && !empty($company)) {
            $companyInputData['company_id'] = $company->id;
            $user = Users::create($companyInputData);
            if (Roles::where('name', $request->role)->exists()) {
                $assign_role = $user->assignRole($request->role);
            } else {
                DB::rollBack();
                return ResponseHandler::validationError(['undefined role!']);
            }

            $success['token'] =  $user->createToken($user['id'])->accessToken;
            $success['data'] =  $user;

            return ResponseHandler::success($success);
        } else {
            return ResponseHandler::validationError(['company not registered.']);
        }

        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }

    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        $data = [];
        try {

            $user = Users::where(["email" => $request->email, 'is_active' => true])->first();

            if ($user) {
                if (Hash::check($request->password, $user->password)) {
                    // if (Auth::guard('web')->attempt(['email' => $request->email, 'password' => $request->password])) {
                    DB::beginTransaction();
                    // $user = Auth::user();
                    $user_data = $user->load(['roles', 'company' => function ($query) {
                        $query->select('id', 'company_name');
                    }]);

                    $data['token'] =  $user->createToken($user->id)->accessToken;
                    $data['data'] = $user_data;

                    return ResponseHandler::success($data);
                } else {
                    return ResponseHandler::validationError(['Invalid email or password!']);
                }
            } else {
                return ResponseHandler::validationError(['Invalid email or password!']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }

    public function getAllUsers(Request $request)
    {
        if (!$users = Users::with('roles')->get()) {
            throw new NotFoundHttpException('Users not found');
        }
        return $users;
    }
}
