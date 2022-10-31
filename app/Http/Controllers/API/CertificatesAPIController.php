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

class CertificatesAPIController extends BaseController
{
    //
    public function index(Request $request)
    {
        // $dutiesData = Duties::getAllDuties();
        // $data = [
        //     'count' => count($dutiesData),
        //     'data'  => $dutiesData
        // ];
        // return ResponseHandler::success($data);
    }

    public function destroy($employeeId, $certificateId)
    {

        $companyId = Auth::user()->company_id;
        if (!isset($companyId) && empty($companyId) && !Auth()->user()->hasRole('super-admin')) {
            return ResponseHandler::validationError(['company_id' => 'This employee does not belongs to any company.']);
        }

        try {

            if (!Employees::whereId($employeeId)->where('is_active', true)->where('company_id', $companyId)->exists()) {
                return ResponseHandler::validationError(['employee_id' => 'Employee ID is required.']);
            }

            if (!EmployeesCertificates::whereId($certificateId)->where('status', true)->exists()) {
                return ResponseHandler::validationError(['certificate_id' => 'Certificate ID is required.']);
            }

            DB::beginTransaction();
            if (EmployeesCertificates::where(['employees_id' => $employeeId, 'id' => $certificateId])->exists()) {


                $response = DutiesEmployees::where([
                    'employees_id' => $employeeId,
                    'id' => $certificateId
                ])->update(['status' => false]);

                if (isset($response) && !empty($response)) {
                    return ResponseHandler::success([], 'Certificate has been deleted successfully.');
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
        $now = new DateTime();
        $company_id = auth()->user()->company_id;
       
        if (!isset($company_id) && empty($company_id) && !Auth()->user()->hasRole('super-admin')) {
            return ResponseHandler::validationError(['company_id' => 'Company Id field is required.']);
        }

        if (!Employees::whereId($request->employee_id)->where('is_active', true)->exists()) {
            return ResponseHandler::validationError(['employee_id' => 'Employee ID field is required.']);
        }

        
        $duty_id_arr = array_column($request->certificates, 'duty_id');

        dd($duty_id_arr);
        // save employee w.r.t ompany, duties, positions and certificates
        $validationRules = [
            'duty_id' => 'required',
            'employee_id' => 'required',
            'certificate_created_at' => 'required|date_format:Y-m-d',
            'certificate_expires_at' => 'required|date_format:Y-m-d',
        ];

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        try {

            DB::beginTransaction();
            $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
            $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));

            // EmployeesCertificates::whereId($certificate_id)->exists();
            
            if ($employee_data->id && !empty($employee_data->id) && !empty($request->file('certificate'))) {

                $fileName = time() . '.' . $request->file('certificate')->extension();
                $type = $request->file('certificate')->getClientMimeType();
                $size = $request->file('certificate')->getSize();
                $request->certificate  = UploadHelper::UploadFile($request->file('certificate'), 'employees_certificates');
                // $request->file('certificate')->move(public_path('certificate'), $fileName);   


                $employee_certificate = [
                    'employees_id' => $employee_data->id,
                    'certificate' => $request->certificate,
                    'status' => true,
                    'certificate_created_at' => $certificate_created_at,
                    'certificate_expires_at' => $certificate_expires_at
                ];
                EmployeesCertificates::create($employee_certificate);
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
}
