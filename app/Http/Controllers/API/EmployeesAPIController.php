<?php

/* *created by Niha Siddiqui 2022-09-10
    * Employee registration controller methods
*/

namespace App\Http\Controllers\API;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Employees;
use App\Models\EmployeesCertificates;
use App\Helpers\ResponseHandler;
use App\Helpers\Constant;
use App\Helpers\UploadHelper;
use App\Models\Company;
use App\Models\Duties;
use App\Models\DutiesEmployees;
use File;
use Validator;
use DateTime;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;

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
            $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
        }, 'position' => function ($query) {
            $query->select('id', 'position_code', 'position_category', 'position_name')->where('status', 1);
        }, 'duties' => function ($query) {
            $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('enrolled_date_started_at', 'enrolled_date_ended_at');
        }, 'certificates' => function ($query) {
            $query->select('id', 'employees_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
        }])
            ->where('employees.is_active', 1)
            ->orderBy('created_at', 'desc')
            ->paginate($request->limit);
        return response()->json(['data' => $data], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      
       
    
        try {
            $uuid = Str::uuid()->toString();
            $now = new DateTime();
            $company_id = auth()->user()->company_id;
          
            // save employee w.r.t ompany, duties, positions and certificates
            $validationRules = [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email|unique:employees,email',
                'gender' => 'required',
                'date_of_birth' => 'required|date_format:Y-m-d',
                'position_id' => 'required',
                'emp_started_period' => 'date|date_format:Y-m-d|nullable',
                'emp_ended_period' => 'date|date_format:Y-m-d|nullable',
                'certificate_created_at' => 'date_format:Y-m-d',
                'certificate_expires_at' => 'date_format:Y-m-d',
                'enrolled_date_started' => 'date_format:Y-m-d|nullable',
                'enrolled_date_ended' => 'date_format:Y-m-d|nullable',
                'emp_started_period' => 'date_format:Y-m-d|nullable',
                'duty_started_at' => 'date_format:Y-m-d',
                'duty_expires_at' => 'date_format:Y-m-d'
            ];

            if (!Auth()->user()->hasRole('super-admin')) {
                $validationRules['company_id'] = 'required';
            }

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            DB::beginTransaction();
            $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
            $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));

            $employee_data = Employees::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'company_id' => $request->company_id,
                'position_id' => $request->position_id,
                'is_active' => ($request->is_active) ? $request->is_active : true
            ]);

            // if (isset($request->duty_id) && !empty($request->duty_id) && empty($request->duty_ended_at)) {
            //     if ($is_duty_exists = Duties::whereId($request->duty_id)->exists()) {
            //         $duty_slug = Duties::whereId($request->duty_id)->pluck('duty_group_slug');

            //         if (array_key_exists($duty_slug[0], Constant::EMPLOYEES_DUTIES)) {
            //             if ($duty_slug != 'special_training') {
            //                 $duty_expires_at = (date('Y-m-d', strtotime($certificate_created_at . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]])));
            //             } else {
            //                 DB::rollBack();
            //                 return ResponseHandler::validationError(['Duty expiry date is required for' . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]]]);
            //             }
            //         } else {
            //             DB::rollBack();
            //             return ResponseHandler::validationError(['Duty expiry date is required for' . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]]]);
            //         }
            //     } else {
            //         DB::rollBack();
            //         return ResponseHandler::validationError(['Duty not found']);
            //     }
            // }

            if(isset($request->duties) && count($request->duties) > 0 && array_key_exists( 'id', $request->duties) && count(array_column($request->duties, 'id')) > 0 ) {
                $duty_ids = array_column($request->duties, 'id');
                $duties_exists = Duties::whereIn('id', $duty_ids)->get();
                
                if($duties_exists->count() != count($duty_ids)) {
                    return ResponseHandler::validationError(['duty_id' => 'duty id not exists.']);
                }
    
            
               $duties_employees = [];
    
                foreach($request->duties as $key => $duty) {
    
                        if(empty($duty['duty_started_at'])) {
                        DB::rollBack();
                        return ResponseHandler::validationError(['duty_started_at' => 'duty_started_at field is required']);
                    }
        
                    if(empty($duty['duty_expires_at'])) {
                        DB::rollBack();
                        return ResponseHandler::validationError(['duty_expires_at' => 'duty_expires_at field is required']);
                    }
                    $duties_employees[$key] = DutiesEmployees::create([
                        'duties_id'     => $duty['id'],
                        'employees_id'  => $employee_data->id,
                        'company_id'    => $employee_data->company_id,
                        'enrolled_date_started_at' => $duty['duty_started_at'],
                        'enrolled_date_ended_at' => $duty['duty_expires_at']
                    ]);
                }
            }
          

            if (isset($employee->id) && !empty($employee->id) && !empty($request->file('certificates'))) {

                $allowedfileExtension = ['pdf'];
                $files = $request->file('certificates');
                $errors = [];


                if ($allowedfileExtension) {
                    foreach ($request->file('certificates') as $mediaFiles) {
                        $fileName = time() . '.' . $mediaFiles->extension();
                        $type = $mediaFiles->getClientMimeType();
                        $size = $mediaFiles->getSize();
                        $request->certificate  = UploadHelper::UploadFile($mediaFiles, 'employees/certificates');


                        $employee_certificate = [
                            'employees_id' => $employee_data->id,
                            'certificate' => $request->certificate,
                            'status' => true,
                            'certificate_created_at' => $certificate_created_at,
                            'certificate_expires_at' => $certificate_expires_at
                        ];

                        EmployeesCertificates::create($employee_certificate);
                    }
                } else {
                    DB::rollBack();
                    return ResponseHandler::validationError(['invalid_file_format']);
                }
            }

            $data = Employees::with(['company', 'position', 'duties' => function ($query) {
                $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('enrolled_date_started_at', 'enrolled_date_ended_at');
            }, 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
            }])->find($employee_data->id);
            return response()->json([
                'message' => 'Employee has been added successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
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
        if (empty($id) || $id == '' || $id == null) {
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
    public function update(Request $request, Employees $employee)
    {

        try {
            //
            $validator = Validator::make($request->all(), [
                'employee_id' => 'required',
                // 'first_name' => 'required',
                // 'last_name' => 'required',
                // 'email' => 'required|email',
                // 'gender' => 'required',
                // 'date_of_birth' => 'required|date_format:Y-m-d',
                // 'position_id' => 'required',
                // 'duty_id' => 'required',
                // 'emp_started_period' => 'date|date_format:Y-m-d|nullable',
                // 'emp_ended_period' => 'date|date_format:Y-m-d|nullable',
                // 'certificate_created_at' => 'date_format:Y-m-d',
                // 'certificate_expires_at' => 'requires|date_format:Y-m-d',
                // 'enrolled_date_started' => 'date_format:Y-m-d|nullable',
                // 'enrolled_date_ended' => 'date_format:Y-m-d|nullable',
                // 'emp_started_period' => 'date_format:Y-m-d|nullable',
                // 'duty_started_at' => 'required|date_format:Y-m-d',
                // 'duty_expires_at' => 'date_format:Y-m-d',
                // 'certificates' => 'mimes:pdf'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            $now = new DateTime();
            if (Employees::where('email', '=', $request->email)->where('id', '!=', $employee->id)->exists()) {
                return ResponseHandler::validationError(['Employee with this email address already exists.']);
            }

            $company_id = Auth()->user()->company_id;

            if (!isset($company_id) && empty($company_id)) {
                return ResponseHandler::validationError(['Company field is required.']);
            }



            DB::beginTransaction();

            $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
            $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));

            if (!Duties::where('id', $request->duty_id)->exists()) {
                return ResponseHandler::validationError(['Undefined employee duty!']);
            }

            $duty_expires_at = (isset($request->duty_expires_at) ? $request->duty_expires_at : '');
            $employee_array = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                //'company_id' => $request->company_id,
                'position_id' => $request->position_id,
                'emp_started_period' => $request->emp_started_period,
                // 'employee_ended_date' => $request->employee_ended_date,
                //'certificate_created_at => $request->certificate_created_at,

            ];

            $employee_data = $employee->whereId($employee->id)->update($employee_array);

            // $duties_employees = DutiesEmployees::where('employee_id', $employee->id)->update([
            //     'duties_id' => $request->duty_id,
            //     'enrolled_date_started_at' => $request->duty_started_at,
            //     'enrolled_date_ended_at' => $duty_expires_at
            // ]);

            if (isset($request->duty_id) && !empty($request->duty_id) && empty($request->duty_expires_at)) {
                if ($is_duty_exists = Duties::whereId($request->duty_id)->exists()) {
                    $duty_slug = Duties::whereId($request->duty_id)->pluck('duty_group_slug');

                    if (array_key_exists($duty_slug[0], Constant::EMPLOYEES_DUTIES)) {
                        if ($duty_slug != 'special_training') {
                            $duty_expires_at = (date('Y-m-d', strtotime($certificate_created_at . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]])));
                        } else {
                            DB::rollBack();
                            return ResponseHandler::validationError(['Duty expiry date is required for' . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]]]);
                        }
                    } else {
                        DB::rollBack();
                        return ResponseHandler::validationError(['Duty expiry date is required for' . CONSTANT::EMPLOYEES_DUTIES_DEFAULT_EXPIRES[$duty_slug[0]]]);
                    }
                } else {
                    DB::rollBack();
                    return ResponseHandler::validationError(['Duty not found']);
                }
            }

            $duties_employees = DutiesEmployees::where('employees_id', $employee->id)->update([
                'duties_id' => $request->duty_id,
                'company_id'    => $employee->company_id,
                'enrolled_date_started_at' => $request->duty_started_at,
                'enrolled_date_ended_at' => $duty_expires_at
            ]);


            if (isset($employee->id) && !empty($employee->id) && !empty($request->file('certificates'))) {

                $allowedfileExtension = ['pdf'];
                $files = $request->file('certificates');
                $errors = [];


                if ($allowedfileExtension) {
                    foreach ($request->file('certificates') as $mediaFiles) {
                        $fileName = time() . '.' . $mediaFiles->extension();
                        $type = $mediaFiles->getClientMimeType();
                        $size = $mediaFiles->getSize();
                        $request->certificate  = UploadHelper::UploadFile($mediaFiles, 'employees_certificates');


                        $employee_certificate = [
                            'employees_id' => $employee->id,
                            'certificate' => $request->certificate,
                            'status' => true,
                            'certificate_created_at' => $certificate_created_at,
                            'certificate_expires_at' => $certificate_expires_at
                        ];

                        EmployeesCertificates::create($employee_certificate);
                    }
                } else {
                    DB::rollBack();
                    return ResponseHandler::validationError(['invalid_file_format']);
                }
            }

            $data = Employees::with(['company', 'position', 'duties' => function ($query) {
                $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('company_id', 'enrolled_date_started_at', 'enrolled_date_ended_at');
            }, 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
            }])->find($employee->id);

            return response()->json([
                'message' => 'Employee has been updated successfully',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
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
            if (Employees::whereId($id)->exists()) {
                $employee_deleted__status = Employees::deleteEmployee($id);
                return ResponseHandler::success(['Employee deleted successfully']);
            } else {
                return ResponseHandler::validationError(['Employee not found']);
            }
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }

    /**
     * Add certificates the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function createUpdate(Request $request)
    {
        $now = new DateTime();
        $company_id = auth()->user()->company_id;
        if (!isset($company_id) && empty($company_id)) {
            return ResponseHandler::validationError('Company field is required.');
        }
        // save employee w.r.t ompany, duties, positions and certificates
        $validationRules = [
            'certificates' => 'required',
            'certificate_created_at' => 'date_format:Y-m-d',
            'certificate_expires_at' => 'requires|date_format:Y-m-d'
        ];

        $employeeData = Employees::find($request->employee_id);

        if (!isset($employeeData->company_id) && empty($employeeData->company_id) && !Auth()->user()->hasRole('super-admin')) {
            $validationRules['company_id'] = 'required';
        }

        $validator = Validator::make($request->all(), $validationRules);



        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        try {

            DB::beginTransaction();
            $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
            $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));

            if (!Duties::where('id', $request->duty_id)->exists()) {
                return ResponseHandler::validationError(['undefined employee duty!']);
            }


            if (!empty($request->file('certificates'))) {

                $allowedfileExtension = ['pdf'];
                $files = $request->file('certificates');
                $errors = [];

                if ($allowedfileExtension) {
                    foreach ($request->file('certificates') as $mediaFiles) {
                        $fileName = time() . '.' . $mediaFiles->extension();
                        $type = $mediaFiles->getClientMimeType();
                        $size = $mediaFiles->getSize();
                        $request->certificate  = UploadHelper::UploadFile($mediaFiles, 'employees/certificates');

                        $employee_certificate = [
                            'employees_id' => $employeeData->id,
                            'certificate' => $request->certificate,
                            'status' => true,
                            'certificate_created_at' => $certificate_created_at,
                            'certificate_expires_at' => $certificate_expires_at
                        ];

                        $response = EmployeesCertificates::create($employee_certificate);
                        $msg = (isset($response) && !empty($response)) ? 'Certificate has been added successfully' : '';
                    }
                } else {
                    DB::rollBack();
                    return ResponseHandler::validationError(['invalid_file_format']);
                }
            }

            $data = Employees::with(['company', 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
            }])->find($employeeData->id);

            return ResponseHandler::success($data, $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }
}
