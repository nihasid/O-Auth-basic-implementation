<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Duties;
use App\Models\DutiesEmployees;
use App\Models\Employees;
use App\Models\EmployeesCertificates;
use Exception;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\Helpers\UploadHelper;
use App\Helpers\Constant;
use DateTime;

class DutiesAPIController extends BaseController
{
    //
    public function index(Request $request)
    {
         // DB::enableQueryLog();
         try {
            $duties = $count = Duties::with(['company' => function ($query) {
                $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                    ->where('status', 1);
            }])->where('status', true);

            if (!Auth()->user()->hasRole('super-admin')) {
                $company_id = Auth()->user()->company_id;
                $duties->where('company_id', $company_id);
            }
            $duties->orderBy('created_at', 'desc');
            $count = $count->get()->count();

            $duties = $duties->simplePaginate(Constant::PAGINATION_LIMIT);
            // dd(DB::getQueryLog());

            $data = [
                'count' => $count,
                'data' => $duties
            ];
            return ResponseHandler::success($data, 200);
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }

    public function storeUpdate(request $request)
    {
        // save employee w.r.t company, duties, positions and certificates
        $validator = Validator::make($request->all(), [
            'duty_name' => 'required',
            'duty_created_at' => 'date|date_format:Y-m-d',
            'duty_ended_at' => 'date|date_format:Y-m-d'
        ]);

        $companyId = Auth()->user()->company_id;
        if (empty($companyId) && !Auth()->user()->hasRole('super-admin')) {
            return ResponseHandler::validationError(['company_id' => 'User does not belong to any company.']);
        }

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {

            DB::beginTransaction();
            $now = new DateTime();
            $input = $request->all();
            $op = 'create';

            $dutyQuery= Duties::where(['company_id' => $companyId, 'status' => true])->where(DB::raw('lower(duty_type_group_name)'), strtolower($input['duty_name']));
            // dd($dutyQuery->exists());
            if ($dutyQuery->exists()) {
                return ResponseHandler::success([], 'Duty with this name already exists.');
            }

            if ((isset($request->id) && !empty($request->id))) {
                $dutyId = $request->id;
                $op = 'update';
            }

            $dutyArr = [
                'duty_type_group_name' => $input['duty_name'],
                'status' => true,
                'duty_group_slug' => str_replace(' ', '_', $input['duty_name'])
            ];

            if(isset($input['duty_description']) && !empty($input['duty_description'])) {
                $dutyArr['duty_group_detail'] = $input['duty_description']; 
            }
            
            $msg = '';

            if ($op == 'create') {
                $dutyArr['company_id'] = $companyId;

                if (Duties::create($dutyArr)) {
                    $msg = 'Duty has been added succesfully.';
                }
            }

            if ($op == 'update') {

                if (Duties::where(['id' => $dutyId, 'company_id' => $companyId, 'status' => true])->update($dutyArr)) {
                    $msg = 'Duty has been updated succesfully.';
                }
            }

            $res = Duties::where($dutyArr)->first();
            
            if ($res) {
                return ResponseHandler::success($res, $msg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }

    public function show($id)
    {
        //
        if (empty($id) || $id == '' || $id == null) {
            return ResponseHandler::validationError(['error' => 'Validation Error.', 'duty_id' => 'duty_id field is required.']);
        }

        $companyId = Auth()->user()->company_id;
        
        if($positionsDetail = Duties::with(['company' => function ($query) {
            $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
        }])->where(['company_id' => $companyId, 'status' => true])->find($id)) {
            return ResponseHandler::success($positionsDetail);
        } else {
            return ResponseHandler::success([], 'Duty not found.');
        }
       
    }


    public function destroyDuty($id)
    {
        //
        try {
            $companyId = Auth()->user()->company_id;
            if (Duties::whereId($id)->where('status', true)->exists()) {
                if (Duties::whereId($id)->where('company_id', $companyId)->update(['status' => false])) {
                    return ResponseHandler::success(['Duty has been deleted successfully']);
                }
            } else {
                return ResponseHandler::validationError(['Duty not found']);
            }
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }

/* *** Employees duties API crud */
    public function destroy($employeeId, $employeeDutyId)
    {
        $companyId = Auth::user()->company_id;
        if (!isset($companyId) && empty($companyId) && !Auth()->user()->hasRole('super-admin')) {
            return ResponseHandler::validationError(['company_id' => 'This employee does not belongs to any company.']);
        }

        try {

            if (!Employees::whereId($employeeId)->where('is_active', true)->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'Employee ID is required.']);
            }

            // if (!Duties::whereId($dutyId)->where('status', true)->exists()) {
            //     return ResponseHandler::validationError(['duty_id' => 'Duty ID is required.']);
            // }

            DB::beginTransaction();
            $employeeDutyQuery = DutiesEmployees::whereId($employeeDutyId);
            if ($employeeDutyQuery->exists()) {
                $dutyId = $employeeDutyQuery->pluck('duties_id')->first();

                $msg = '';
                if (DutiesEmployees::whereId($employeeDutyId)->update(['status' => false])) {
                    $msg .= 'Employee with this duty has been deleted';
                    /* *** Certificate Deletion starts *** */
                    $certificateQuery = EmployeesCertificates::where(['employees_id' => $employeeId, 'duties_id' => $dutyId, 'status' => true]);
                    if ($certificateQuery->exists()) {

                        if ($certificateQuery->update(['status' => false])) {
                            $msg .= " with certificate's";
                        }
                    }

                    return ResponseHandler::success([], (!empty($msg) ? $msg.'.' : ''));
                    /* *** Certificate Deletion ends *** */
                }

            } else {
                return ResponseHandler::validationError(['duty_id' => 'Employee with this duty does not exists.']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }

    public function createUpdate(Request $request)
    {

        $companyId = Auth::user()->company_id;
        if (!isset($companyId) && empty($companyId) && !Auth()->user()->hasRole('super-admin')) {
            return ResponseHandler::validationError(['company_id' => 'This employee does not belongs to any company.']);
        }

        try {

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required',
                'duty_id' => 'required',
                'enrolled_started_date' => 'date_format:Y-m-d',
                'enrolled_ended_date' => 'date_format:Y-m-d'
            ]);

            if ($validator->fails()) {
                return ResponseHandler::validationError($validator->errors());
            }

            if (!Employees::whereId($request->employee_id)->where('is_active', true)->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'Employee does not active.']);
            }

            if (!Duties::whereId($request->duty_id)->where('status', true)->exists()) {
                return ResponseHandler::validationError(['duty_id' => 'Duty does not active.']);
            }

            DB::beginTransaction();
            // *** update Duty
            $storeDuty = [
                'enrolled_date_started_at' => $request->enrolled_started_date,
                'enrolled_date_ended_at' => $request->enrolled_ended_date,
                'status' => true,
                'company_id' => $companyId
            ];

            $msg = '';
            $empoyeeDuties = DutiesEmployees::where(['employees_id' => $request->employee_id, 'duties_id' => $request->duty_id]);
            if ($empoyeeDuties->exists()) {
                $employeeDetail = $empoyeeDuties->update($storeDuty);
                $msg = 'Employee with this duty updated successfully.';
            } else {
                $storeDuty['employees_id'] = $request->employee_id;
                $storeDuty['duties_id'] = $request->duty_id;

                $employeeDetail = DutiesEmployees::create($storeDuty);
                $msg = 'Employee successfully enrolled in the duty.';
            }


            // *** Certificate create/ update starts ***

            $employeeDetail = DutiesEmployees::where($storeDuty)->select('id', 'employees_id', 'company_id', 'duties_id')->first();
            $certificatesQuery = EmployeesCertificates::where(['employees_id' => $employeeDetail->employees_id, 'duties_id' => $employeeDetail->duties_id]);

            $employeeCertificate = [
                'employees_id' => $employeeDetail->employees_id,
                'duties_id' => $employeeDetail->duties_id,
                'status' => true
            ];
            if (isset($request->enrolled_started_date) && !empty($request->enrolled_started_date)) {

                $employeeCertificate['certificate_created_at'] = $request->enrolled_started_date;
            }

            if (isset($request->enrolled_ended_date) && !empty($request->enrolled_ended_date)) {
                $employeeCertificate['certificate_expires_at'] = $request->enrolled_ended_date;
            }

            if ($employeeDetail && !empty($request->file('certificate'))) {
                $allowedfileExtension = ['pdf'];
                $certificate = $request->file('certificate');



                if ($allowedfileExtension) {

                    $fileName = time() . '.' . $certificate->extension();
                    $type = $certificate->getClientMimeType();
                    $size = $certificate->getSize();
                    $certificateUrl  = UploadHelper::UploadFile($certificate, 'employees/certificates');
                    $employeeCertificate['certificate'] = $certificateUrl;
                } else {

                    return ResponseHandler::validationError(['file_format' => 'invalid_file_format']);
                }
            }

            if (isset($employeeDetail) && !empty($employeeDetail->id) && $certificatesQuery->exists()) {


                $certificatesQuery = $certificatesQuery->first();
                $response = $certificatesQuery->whereId($certificatesQuery->id)->update($employeeCertificate);
            } else {
                if (!empty($request->file('certificate'))) {
                    $response = EmployeesCertificates::create($employeeCertificate);
                }
            }


            // *** Certificate create/ update ends ***

            $data = Employees::with(['company' => function ($query) {
                $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                    ->where('status', 1);
            }, 'duties' => function ($query) {
                $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('id', 'enrolled_date_started_at', 'enrolled_date_ended_at')->wherePivot('status', true);
            }, 'certificates' => function ($query) {
                $query->select('id', 'employees_id', 'duties_id', 'certificate', 'certificate_created_at', 'certificate_expires_at')->where('status', true)->orderBy('certificate_created_at');
            }])->where('employees.is_active', 1)->find($employeeDetail->employees_id);

            return ResponseHandler::success($data, $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }
}
