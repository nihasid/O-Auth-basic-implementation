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
use Validator;
use DateTime;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeesAPIController extends BaseController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // DB::enableQueryLog();
        // get all employees list with company, duties, positions and certificates
        $employees = $count = Employees::with(['company' => function ($query) {
            $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
        }, 'position' => function ($query) {
            $query->select('id', 'position_code', 'position_category', 'position_name')->where('status', 1);
        }, 'duties' => function ($query) {
            $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')
                ->withPivot('id', 'enrolled_date_started_at', 'enrolled_date_ended_at')
                ->wherePivot('status', true);
        }, 'certificates' => function ($query) {
            $query->select('id', 'employees_id', 'duties_id',  'certificate', 'certificate_created_at', 'certificate_expires_at', 'status')->where('status', true)->orderBy('certificate_created_at');
        }])
            ->where('employees.is_active', 1);

        if (!Auth()->user()->hasRole('super-admin')) {
            $company_id = auth()->user()->company_id;
            $employees->where('company_id', $company_id);
        }
        $employees->orderBy('created_at', 'desc');
        $count = $count->get()->count();
        $employees = $employees->simplePaginate(Constant::PAGINATION_LIMIT);
        // dd(DB::getQueryLog());

        $data = [
            'count' => $count,
            'data' => $employees
        ];
        return ResponseHandler::success($data, 200);
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
            $now = new DateTime();
            $company_id = auth()->user()->company_id;

            // *** validation Starts ***
            $validationRules = [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required',
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
            // $validationRules['email'] = ['required', Rule::unique('users')->where(function ($query) use ($request, $company_id) {
            //     $query->where('id', $request->employee_id)->where("company_id", $company_id)->where('is_active', true);
            // })];

            if (Employees::where(['email' => $request->email, 'company_id' => $company_id, 'is_active' => true])->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'employee with this email already exists.']);
            }

            if (!isset($company_id) && empty($company_id) && !Auth()->user()->hasRole('super-admin')) {
                $validationRules['company_id'] = 'required';
            }

            $validator = Validator::make($request->all(), $validationRules);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }

            // *** validation ends ***

            DB::beginTransaction();
            $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
            $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));

            // *** Emaployee data Store starts ***
            $employee_data = Employees::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'company_id' => ($request->company_id) ? $request->company_id : $company_id,
                'position_id' => $request->position_id,
                'is_active' => ($request->is_active) ? $request->is_active : true
            ]);;
            $employee_id = $employee_data->id;
            $emp_company_id = $employee_data->id;
            // *** Emaployee data Store ends ***

            //*** Duty data starts ***/
            $dutiesCertificatesArr = $request->duties;

            if (isset($request->duties) && count($request->duties) > 0) {
                $duty_ids = array_column($request->duties, 'duty_id');
                $duties_exists = Duties::whereIn('id', $duty_ids)->get();

                if ($duties_exists->count() != count($duty_ids)) {
                    return ResponseHandler::validationError(['duty_id' => 'duty id not exists.']);
                }


                $duties_employees = [];

                for ($i = 0; $i < count($dutiesCertificatesArr); $i++) {

                    // *** Assign duty with employeees starts *** 
                    if (!isset($dutiesCertificatesArr[$i]['duty_id']) && empty($dutiesCertificatesArr[$i]['duty_id'])) {
                        return ResponseHandler::validationError(['duty_id' => 'duty_id field is required.']);
                    }

                    if (Duties::whereId($dutiesCertificatesArr[$i]['duty_id'])->exists()) {

                        $validationRules = [
                            
                            'duty_started_at' => 'required|date_format:Y-m-d',
                            'duty_expires_at' => 'required|date_format:Y-m-d'
                        ];
            
                        $validatorError = Validator::make($dutiesCertificatesArr[$i], $validationRules);
            
                        if ($validatorError->fails()) {
                            DB::rollBack();
                            return ResponseHandler::validationError($validatorError->errors());
                        }
                        //  *** validation for duty ***

                        if (empty($dutiesCertificatesArr[$i]['duty_started_at'])) {
                            DB::rollBack();
                            return ResponseHandler::validationError(['duty_started_at' => 'duty_started_at field is required']);
                        }

                        if (empty($dutiesCertificatesArr[$i]['duty_expires_at'])) {
                            DB::rollBack();
                            return ResponseHandler::validationError(['duty_expires_at' => 'duty_expires_at field is required']);
                        }

                        $duties_employees[$i] = DutiesEmployees::create([
                            'duties_id'     => $dutiesCertificatesArr[$i]['duty_id'],
                            'employees_id'  => $employee_id,
                            'company_id'    => $emp_company_id,
                            'enrolled_date_started_at' => $dutiesCertificatesArr[$i]['duty_started_at'],
                            'enrolled_date_ended_at' => $dutiesCertificatesArr[$i]['duty_expires_at'],
                            'status' => true
                        ]);

                        // *** Assign duty with employeees ends *** 

                        // *** Add Certificate w.r.t employee duty starts ***
                        if (!empty($request->file('duties')[$i])) {

                            $certificate_created_at = $dutiesCertificatesArr[$i]['duty_started_at'];
                            $certificate_expires_at = $dutiesCertificatesArr[$i]['duty_expires_at'];
                            $allowedfileExtension = ['pdf'];
                            $certificate = $request->file('duties')[$i]['certificate'];
                            $errors = [];

                            if ($allowedfileExtension) {

                                $fileName = time() . '.' . $certificate->extension();
                                $type = $certificate->getClientMimeType();
                                $size = $certificate->getSize();
                                $certificateUrl  = UploadHelper::UploadFile($certificate, 'employees/certificates');

                                $employee_certificate = [
                                    'employees_id' => $employee_id,
                                    'duties_id' => $dutiesCertificatesArr[$i]['duty_id'],
                                    'certificate' => $certificateUrl,
                                    'status' => true,
                                    'certificate_created_at' => $certificate_created_at,
                                    'certificate_expires_at' => $certificate_expires_at
                                ];

                                $response = EmployeesCertificates::create($employee_certificate);
                            } else {
                                DB::rollBack();
                                return ResponseHandler::validationError(['file_format' => 'invalid_file_format']);
                            }
                        }
                    } else {
                        DB::rollBack();
                        return ResponseHandler::validationError(['duty_id' => 'duty_id not found.']);
                    }
                    // *** Add Certificate w.r.t employee duty ends ***
                }
            }

            // *** duty data ends ***

            $data = Employees::with(['company', 'position', 'duties' => function ($query) {
                $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('enrolled_date_started_at', 'enrolled_date_ended_at');
            }, 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'duties_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->whereNotNull('status')->orderBy('certificate_created_at');
            }])->where('is_active', true)->find($employee_id);
            return ResponseHandler::success($data, 'Employee has been added successfully');
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
        $employee_data = Employees::with(['company' => function ($query) {
            $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
        }, 'position' => function ($query) {
            $query->select('id', 'position_code', 'position_category', 'position_name')->where('status', 1);
        }, 'duties' => function ($query) {
            $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')
                ->withPivot('id', 'enrolled_date_started_at', 'enrolled_date_ended_at')
                ->wherePivot('status', true);
        }, 'certificates' => function ($query) {
            $query->select('id', 'employees_id', 'duties_id',  'certificate', 'certificate_created_at', 'certificate_expires_at', 'status')->where('status', true)->orderBy('certificate_created_at');
        }])->where('employees.is_active', 1)->find($id);

        return ResponseHandler::success($employee_data);
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
                'email' => 'email',
                // 'gender' => 'required',
                'date_of_birth' => 'date_format:Y-m-d',
                // 'position_id' => 'required',
                // 'duty_id' => 'required',
                'emp_started_period' => 'date|date_format:Y-m-d',
                'emp_ended_period' => 'date|date_format:Y-m-d',
                // 'certificate_created_at' => 'date_format:Y-m-d',
                // 'certificate_expires_at' => 'required|date_format:Y-m-d',
                // 'enrolled_date_started' => 'date_format:Y-m-d|nullable',
                // 'enrolled_date_ended' => 'date_format:Y-m-d|nullable',
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

            if (!isset($company_id) && empty($company_id) && !Auth()->user()->hasRole('super-admin')) {
                return ResponseHandler::validationError(['Company field is required.']);
            }



            DB::beginTransaction();


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
            $input = $request->all();
            unset($input['employee_id']);

            $employee_data = $employee->whereId($employee->id)->update($input);

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

            return ResponseHandler::success([$data], 'Employee detail has been updated successfully.');
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
                $employee_deleted__status = Employees::where('id', $id)->update(['is_active' => false]);
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
    public function createCertificate(Request $request)
    {
        $now = new DateTime();

        // save employee w.r.t ompany, duties, positions and certificates
        $validationRules = [
            'employee_id' => 'required'
        ];

        $certificates_arr = $request->certificates;
        $duty_id_arr = array_column($certificates_arr, 'duty_id');
        $employeeData = Employees::find($request->employee_id);

        if (!isset($employeeData->company_id) && empty($employeeData->company_id) && !Auth()->user()->hasRole('super-admin')) {
            $validationRules['company_id'] = 'required';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        try {
            $msg = '';
            DB::beginTransaction();


            // if (!Duties::where('id', $request->duty_id)->exists()) {
            //     return ResponseHandler::validationError(['undefined employee duty!']);
            // }
            for ($i = 0; $i < count($certificates_arr); $i++) {
                if (!empty($request->file('certificates')[$i])) {
                    if (DutiesEmployees::where(['employees_id' => $request->employee_id, 'duties_id' => $certificates_arr[$i]['duty_id']])->exists()) {

                        if (!isset($certificates_arr[$i]['duty_id']) && empty($certificates_arr[$i]['duty_id'])) {
                            return ResponseHandler::validationError(['duty_id' => 'duty_id field is required for adding certificate.']);
                        }
                        if (!isset($certificates_arr[$i]['certificate_created_at']) && empty($certificates_arr[$i]['certificate_created_at'])) {
                            return ResponseHandler::validationError(['certificate_created_at' => 'certificate_created_at field is required for adding certificate.']);
                        }
                        if (!isset($certificates_arr[$i]['certificate_expires_at']) && empty($certificates_arr[$i]['certificate_expires_at'])) {
                            return ResponseHandler::validationError(['certificate_expires_at' => 'certificate_expires_at field is required for adding certificate.']);
                        }

                        $duty_id = $certificates_arr[$i]['duty_id'];
                        $certificate_created_at = (!empty($certificates_arr[$i]['certificate_created_at']) ? $certificates_arr[$i]['certificate_created_at'] : $now->format('Y-m-d'));
                        $certificate_expires_at = (!empty($certificates_arr[$i]['certificate_expires_at']) ? $certificates_arr[$i]['certificate_expires_at'] : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));
                        $allowedfileExtension = ['pdf'];
                        $certificate = $request->file('certificates')[$i]['certificate'];
                        $errors = [];

                        if ($allowedfileExtension) {

                            $fileName = time() . '.' . $certificate->extension();
                            $type = $certificate->getClientMimeType();
                            $size = $certificate->getSize();
                            $certificateUrl  = UploadHelper::UploadFile($certificate, 'employees/certificates');

                            $employee_certificate = [
                                'employees_id' => $employeeData->id,
                                'duties_id' => $certificates_arr[$i]['duty_id'],
                                'certificate' => $certificateUrl,
                                'status' => true,
                                'certificate_created_at' => $certificate_created_at,
                                'certificate_expires_at' => $certificate_expires_at
                            ];

                            $response = EmployeesCertificates::create($employee_certificate);
                            // dd($response);
                            $msg = 'Certificate has been added successfully';
                        } else {
                            DB::rollBack();
                            return ResponseHandler::validationError(['file_format' => 'invalid_file_format']);
                        }
                    } else {
                        DB::rollBack();
                        return ResponseHandler::validationError(['duty_id' => 'Employee not enrolled in specified duty.']);
                    }
                }
            }

            $data = Employees::with(['company', 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'duties_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->where('status', true)->orderBy('certificate_created_at');
            }])->find($employeeData->id);

            return ResponseHandler::success($data, $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }

    /**
     * delete certificates the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyCertificate($employeeId, $certificateId)
    {
        //
        try {
            if (Employees::whereId($employeeId)->exists()) {
                if (EmployeesCertificates::whereId($certificateId)->exists()) {
                    $employeeCertificateDeletedStatus = EmployeesCertificates::where('id', $certificateId)->update(['status' => false]);
                    return ResponseHandler::success(['Certificate has been deleted successfully']);
                } else {
                    return ResponseHandler::validationError(['Certificate not found']);
                }
            } else {
                return ResponseHandler::validationError(['Employee not found']);
            }
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }

    public function updateCertificate(Request $request)
    {
        $now = new DateTime();

        // save employee w.r.t ompany, duties, positions and certificates
        $validationRules = [
            'employee_id' => 'required',
            'certificate_id' => 'required',
            'certificate_created_at' => 'date_format:Y-m-d',
            'certificate_expires_at' => 'date_format:Y-m-d'
        ];

        $employeeData = Employees::where('is_active', true)->find($request->employee_id);

        if (!isset($employeeData->company_id) && empty($employeeData->company_id) && !Auth()->user()->hasRole('super-admin')) {
            $validationRules['company_id'] = 'required';
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return ResponseHandler::validationError($validator->errors());
        }

        try {
            $msg = '';
            DB::beginTransaction();


            $errors = [];
            if (EmployeesCertificates::whereId($request->certificate_id)->where('employees_id', $request->employee_id)->exists()) {

                $employee_certificate = [
                    'employees_id' => $employeeData->id,
                    // 'duties_id' => $employeeData->duty_id,
                    'status' => true
                ];

                if (isset($request->certificate_created_at) && !empty($request->certificate_created_at))
                    $employee_certificate['certificate_created_at'] = $request->certificate_created_at;

                if (isset($request->certificate_expires_at) && !empty($request->certificate_expires_at))
                    $employee_certificate['certificate_expires_at'] = $request->certificate_expires_at;


                if (count($errors) > 0) {
                    return ResponseHandler::validationError($errors);
                }

                $allowedfileExtension = ['pdf'];
                $certificate = $request->file('certificate');

                $employee_certificate = [
                    'employees_id' => $employeeData->id,
                    // 'duties_id' => $employeeData->duty_id,
                    'status' => true
                ];

                if (!empty($request->file('certificate'))) {
                    if ($allowedfileExtension) {

                        $fileName = time() . '.' . $certificate->extension();
                        $type = $certificate->getClientMimeType();
                        $size = $certificate->getSize();
                        $certificateUrl  = UploadHelper::UploadFile($certificate, 'employees/certificates');
                        $employee_certificate['certificate'] = $certificateUrl;
                    } else {
                        DB::rollBack();
                        return ResponseHandler::validationError(['file_format' => 'invalid_file_format']);
                    }
                }

                $response = EmployeesCertificates::whereId($request->certificate_id)->update($employee_certificate);
                $msg = 'Certificate has been updated successfully';
            } else {
                DB::rollBack();
                return ResponseHandler::validationError(['certificate_id' => 'Certificate not found.']);
            }



            $data = Employees::with(['company', 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'duties_id', 'certificate', 'certificate_created_at', 'certificate_expires_at', 'status')->where('status', true)->orderBy('certificate_created_at');
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
