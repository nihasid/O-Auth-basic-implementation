<?php
   
namespace App\Http\Controllers\API;
   
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
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
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'role' => 'required'
        ]);

   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
        DB::beginTransaction();
   
        $input = $request->all();
        // $input['password'] = $input['password'];
        
        $user = User::create($input);
        $assign_role = $user->assignRole($request->role);
        $success['token'] =  $user->createToken('MyApp')->accessToken;
        DB::commit();
        $success['name'] =  $user;
   
        return $this->sendResponse($success, 'User register successfully.');
    }
   
    /**
     * Login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {

    	
        if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){ 
           
            $user = Auth::user(); 
            $user_data = User::with(['roles', 'company' => function( $query ){
            		$query->select('id', 'company_name');
            	}])->select('id', 'name', 'email', 'company_id')
                ->where('id', auth()->id())->first();

            $success['token'] =  $user->createToken('MyApp')-> accessToken; 
            // $success['name'] =  $user->name;
            $success['data'] = [
            	'users' => $user_data
            ];
   
            return $this->sendResponse($success, 'User login successfully.');
        } 
        else{ 
            return $this->sendError('Unauthorised.', ['error'=>'Unauthorised']);
        } 
    }

    public function getAllUsers(Request $request) {
        if( !$users = User::with('roles')->get()) {
            throw new NotFoundHttpException('Users not found');
        }
        return $users;
    }
}