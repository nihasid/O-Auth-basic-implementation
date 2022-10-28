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
            $now = new DateTime();
            $company_id = auth()->user()->company_id;
            if (!isset($company_id) && empty($company_id)) {
                return ResponseHandler::validationError('Company field is required.');
            }
            // save employee w.r.t ompany, duties, positions and certificates
            $validationRules = [
                'employee_id' => 'required',
                'certificate_created_at' => 'date_format:Y-m-d',
                'certificate_expires_at' => 'requires|date_format:Y-m-d',
            ];
    
            if (!Auth()->user()->hasRole('super-admin')) {
                $validationRules['company_id'] = 'required';
            }
    
            $validator = Validator::make($request->all(), $validationRules);
    
    
    
            if ($validator->fails()) {
                return $this->sendError('Validation Error.', $validator->errors());
            }
    
            try {
    
                DB::beginTransaction();
                $certificate_created_at = (!empty($request->certificate_created_at) ? $request->certificate_created_at : $now->format('Y-m-d'));
                $certificate_expires_at = (!empty($request->certificate_expires_at) ? $request->certificate_expires_at : (date('Y-m-d', strtotime($certificate_created_at . " +1 year"))));
    
                if (!Duties::where('id', $request->duty_id)->exists()) {
                    return ResponseHandler::validationError(['undefined employee duty!']);
                }
                $duty_expires_at = (isset($request->duty_expires_at) ? $request->duty_expires_at : '');
    
                if (isset($request->duty_id) && !empty($request->duty_id)) {
                    if ($is_duty_exists = Duties::whereId($request->duty_id)->exists()) {
                        $duty_slug = Duties::whereId($request->duty_id)->pluck('duty_group_slug');
    
                    } else {
                        DB::rollBack();
                        return ResponseHandler::validationError(['Duty not found']);
                    }
                }
    EmployeesCertificates::where(['employees_id' => $request->employee_id, 'duty_id' => $request->duty_id])->exists();
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
}
