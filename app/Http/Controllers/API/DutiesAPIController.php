<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Duties;
use App\Models\DutiesEmployees;
use App\Models\Employees;
use Exception;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Validator;

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

    public function destroy($employeeId, $dutyId)
    {


        $companyId = Auth::user()->company_id;
        if (!isset($companyId) && empty($companyId)) {
            return ResponseHandler::validationError(['company_id' => 'This employee does not belongs to any company.']);
        }

        try {



            if (!Employees::whereId($employeeId)->where('is_active', true)->where('company_id', $companyId)->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'Employee ID is required.']);
            }

            if (!Duties::whereId($dutyId)->where('status', true)->exists()) {
                return ResponseHandler::validationError(['duty_id' => 'Duty ID is required.']);
            }

            DB::beginTransaction();
            if (DutiesEmployees::where(['employees_id' => $employeeId, 'duties_id' => $dutyId])->exists()) {


                $response = DutiesEmployees::where([
                    'employees_id' => $employeeId,
                    'duties_id' => $dutyId,
                    'company_id' => $companyId
                ])->update(['status' => false]);

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
        if (!isset($companyId) && empty($companyId)) {
            return ResponseHandler::validationError(['company_id' => 'This employee does not belongs to any company.']);
        }

        try {

            $validator = Validator::make($request->all(), [
                'employee_id' => 'required',
                'duty_id' => 'required',
                'duty_id' => 'required'
            ]);

            if ($validator->fails()) {
                return ResponseHandler::validationError($validator->errors());
            }

            if (!Employees::whereId($request->employee_id)->where('is_active', true)->where('company_id', $companyId)->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'Employee ID is required.']);
            }

            if (!Duties::whereId($request->duty_id)->where('status', true)->exists()) {
                return ResponseHandler::validationError(['duty_id' => 'Duty ID is required.']);
            }

            DB::beginTransaction();
            $storeDuty = [
                'enrolled_date_started_at' => $request->enrolled_started_date,
                'enrolled_date_ended_at' => $request->enrolled_ended_date,
                'status' => true,
                'company_id' => $companyId
            ];
            $msg = '';
            $empoyeeDuties = DutiesEmployees::where(['employees_id' => $request->employee_id, 'duties_id' => $request->duty_id]);
            if ($empoyeeDuties->exists()) {
                $response = $empoyeeDuties->update($storeDuty);

                $msg = 'Employee with this duty updated successfully.';
            } else {
                $storeDuty['employees_id'] = $request->employee_id;
                $storeDuty['duties_id'] = $request->duty_id;

                $response = DutiesEmployees::create($storeDuty);
                $msg = 'Employee successfully enrolled in the duty.';
            }

            if ($response) {

                $data = Employees::with(['company' => function ($query) {
                    $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                        ->where('status', 1);
                }, 'duties' => function ($query) {
                    $query->select('duties.id', 'duty_type_group', 'duty_type_group_name', 'duty_group_detail')->withPivot('enrolled_date_started_at', 'enrolled_date_ended_at')->wherePivot('status', true);
                }])
                    ->where('employees.is_active', 1)->find($request->employee_id);
            }

            if (isset($response) && !empty($response)) {
                return ResponseHandler::success($data, $msg);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseHandler::serverError($e);
        } finally {
            DB::commit();
        }
    }
}
