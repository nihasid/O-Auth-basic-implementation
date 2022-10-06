<?php
/* *created by Niha Siddiqui 2022-09-02
    * Company registration controller methods
*/
namespace App\Http\Controllers\API;

use App\Http\Controllers\API\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Roles;
use Validator;
use DB;

class CompanyAPIController extends BaseController
{
    //
    public function index(Request $request)
    {
        // \DB::enableQueryLog(); // Enable query log
        
        // get all Companies list with company, duties, positions and certificates
        $data = Company::leftJoin('employees', 'employees.company_id', '=', 'companies.id')
        ->select('companies.id', 'company_type_id', 'company_name', 'company_department', 'company_started_at', 'company_ended_at',\DB::raw('COUNT(employees.id) as employees'))
        ->groupBy('companies.id')->whereNotNull('status')
            ->orderBy('companies.created_at', 'desc')
            ->paginate($request->limit);
            // dd(\DB::getQueryLog()); // Show results of log
        return response()->json(['data'=> $data], 200);
    }

    public function store(request $request)
    {
        // save employee w.r.t ompany, duties, positions and certificates
        $validator = Validator::make($request->all(), [
            'company_type_id' => 'required',
            'company_name' => 'required',
            'company_department' => 'required',
            'company_started_at' => 'date|date_format:Y-m-d|required',
            'company_ended_at' => 'date|date_format:Y-m-d|nullable'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }

        $input = $request->all();
        try{
            $result = Company::create($input);
            if($result) {
                return response()->json(['message' => 'company added succesfully.', 'data' => $result], 200);
            }
            
        } catch(Exception $e) {
            dd($e->getMessage());
        }
        

    }

    public function show($id)
    {
        $data = Company::with('employees')->find($id);
        return response()->json([
            'data' => $data
        ], 200);

    }

    public function update(Request $request, $id)
    {
        // save employee w.r.t ompany, duties, positions and certificates
        // $validator = Validator::make($request->all(), [
        //     'company_type_id' => 'required',
        //     'company_name' => 'required',
        //     'company_department' => 'required',
        //     'company_started_at' => 'date|date_format:Y-m-d|required',
        //     'company_ended_at' => 'date|date_format:Y-m-d|nullable'
        // ]);
   
        // if($validator->fails()){
        //     return $this->sendError('Validation Error.', $validator->errors());       
        // }

        $input = $request->all();
        dd($input);
        // try{
        //     $result = Company::create($input);
        //     if($result) {
        //         return response()->json(['message' => 'company added succesfully.', 'data' => $result], 200);
        //     }
            
        // } catch(Exception $e) {
        //     dd($e->getMessage());
        // }

    }

    public function destroy($id)
    {

    }
}
