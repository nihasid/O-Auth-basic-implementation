<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Employees;
use App\Models\EmployeesCertificates;
use App\Models\Company;
use File;
use Validator;
use DateTime;

class EmployeesAPIController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        // get all employees list with company, duties, positions and certificates
        $data = Employees::with(['company' => function ($query) {
                $query->select('id','company_type_id', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
            }, 'position' => function( $query) {
                $query->select('id', 'position_code', 'position_category', 'position_name')->where('status', 1);
            }, 'duties' => function ( $query) {
                $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail');
            }, 'certificates' => function ( $query) {
                $query->select('id', 'employees_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
            }])
            ->where('Employees.is_active', 1)
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit);
        return response()->json(['data'=> $data], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $now = new DateTime();
        
        // save employee w.r.t ompany, duties, positions and certificates
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'gender' => 'required',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'company_id' => 'required',
            'position_id' => 'required',
            // 'duty_id' => 'required',
            'emp_started_period' => 'date|date_format:Y-m-d|nullable',
            'emp_ended_period' => 'date|date_format:Y-m-d|nullable',
            'certificate_expires_at' => 'required|date_format:Y-m-d',
            'enrolled_date_started' => 'date_format:Y-m-d|nullable',
            'enrolled_date_ended' => 'date_format:Y-m-d|nullable',
            'emp_started_period' => 'date_format:Y-m-d|nullable'
        ]);
   
        if($validator->fails()){
            return $this->sendError('Validation Error.', $validator->errors());       
        }
      
       try {
        
            $emp = Employees::create($request->all());
            if(isset($emp) && !empty($emp)) 
            {
                if($request->duty_id) {
                    $employee_duties = [
                        'employees_id' => $emp->id,
                        'duties_id' => $request->duty_id,
                        'status' => true,
                        'enrolled_date_started' => (isset($request->certificate_created_at) && !empty($request->certificate_created_at))?$request->certificate_created_at:$now->format('Y-m-d'),
                        'enrolled_date_ended' => (isset($request->certificate_expires_at) && !empty($request->certificate_expires_at))?$request->certificate_expires_at:'',
                    ];

                }
               
                if($emp->id && !empty($emp->id)) {
                    if(!empty($request->file('certificate'))) {
                    $fileName = time() . '.'. $request->file('certificate')->extension();  
                    $type = $request->file('certificate')->getClientMimeType();
                    $size = $request->file('certificate')->getSize();
                    $request->file('certificate')->move(public_path('certificate'), $fileName);
                    $url = $fileName;
                    $request->certificate = $url;
                }
    
                    $employee_certificate = [
                        'employees_id' => $emp->id,
                        'certificate' => $fileName,
                        'status' => true,
                        'certificate_created_at' => (isset($request->certificate_created_at) && !empty($request->certificate_created_at))?$request->certificate_created_at:$now->format('Y-m-d'),
                        'certificate_expires_at' => (isset($request->certificate_expires_at) && !empty($request->certificate_expires_at))?$request->certificate_expires_at:'',
                    ];
                    EmployeesCertificates::create($employee_certificate);
                }
                $data = Employees::with('company', 'position', 'duties', 'certificates')->first();
                return response()->json([
                    'message'=>'Employee has been added successfully',
                    'data' => $data 
                ], 200);
            } else {
                return response()->json([
                    'message' => 'error occured while adding an employee'
                ], 500);
            }
            
           
          
       } catch(Exception $e) {
            dd($e->getMessage());
       }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        if(empty($id) || $id == '' || $id == null)
        {
            return response()->json(['error' => 'Validation Error.', 'message' => 'employee id is required.'], 419);       
        }
        $employee_data = Employees::with('company', 'position', 'duties', 'certificates')->find($id);
        return response()->json([
            'data' => $employee_data 
        ], 200);
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
        //
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
    }
}
