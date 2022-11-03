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

class DutiesAPIController extends BaseController
{
    //
    public function index(Request $request)
    {
        $dutiesData = Duties::getAllDuties();
        $data = [
            'count' => count($dutiesData),
            'data'  => $dutiesData
        ];
        return ResponseHandler::success($data);
    }

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
            if (DutiesEmployees::whereId($employeeDutyId)->exists()) {


                $response = DutiesEmployees::whereId($employeeDutyId)->update(['status' => false]);

                if (isset($response) && !empty($response)) {
                    return ResponseHandler::success([], 'Duty has been deleted successfully.');
                }
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

        // try {

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
            // 'duties_id' => $employeeData->duty_id,
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
                DB::rollBack();
                return ResponseHandler::validationError(['file_format' => 'invalid_file_format']);
            }
        }
        
        if (isset($employeeDetail) && !empty($employeeDetail->id) && $certificatesQuery->exists()) {
            // DB::rollBack();

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
        }])
            ->where('employees.is_active', 1)->find($employeeDetail->employees_id);



        return ResponseHandler::success($data, $msg);

        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return ResponseHandler::serverError($e);
        // } finally {
        //     DB::commit();
        // }
    }
}
