<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Users;
use App\Helpers\ResponseHandler;
use Illuminate\Support\Facades\Auth;
use App\Models\Roles;
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
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'password' => 'required|min:6|regex:/^.*(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/',
            'c_password' => 'required|same:password',
            'role' => 'required',
            // 'company_id' => 'required'
        ]);
        
   
        if($validator->fails()){
            return ResponseHandler::validationError($validator->errors());     
        }
        try{
            DB::beginTransaction();
            
            $input = $request->all();
            $input['is_active'] = 1;
            
            $user = Users::create($input);
            
            if(Roles::where('name', $request->role)->exists()) {
                $assign_role = $user->assignRole($request->role);
            } else {
                DB::rollBack();
                return ResponseHandler::validationError(['undefined role!']);
            }
            
            $success['token'] =  $user->createToken($user['id'])->accessToken;
            $success['data'] =  $user;
    
            return ResponseHandler::success($success);
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
   
        if($validator->fails()){
            return ResponseHandler::validationError($validator->errors());     
        }
        $data = [];
    	try {
            if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
                DB::beginTransaction();
                $user = Auth::user(); 
                $user_data = Users::with(['roles', 'company' => function( $query ){
                        $query->select('id', 'company_name');
                    }])->select('id', 'first_name', 'last_name', 'email', 'date_of_birth', 'company_id')
                    ->where('id', auth()->id())->first();

                $data['token'] =  $user->createToken(auth()->user()->id)-> accessToken; 
                $data['data'] = $user_data;
    
                return ResponseHandler::success($data);
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

    public function getAllUsers(Request $request) {
        if( !$users = Users::with('roles')->get()) {
            throw new NotFoundHttpException('Users not found');
        }
        return $users;
    }
}