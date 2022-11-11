<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseHandler;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Positions;
use App\Helpers\Constant;
use DB;
use Validator;
use DateTime;
use Illuminate\Support\Facades\Auth;

class PositionsController extends BaseController
{
    //
    public function index(Request $request)
    {
        // DB::enableQueryLog();
        try {
            $positions = $count = Positions::with(['company' => function ($query) {
                $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                    ->where('status', 1);
            }])->where('status', true);

            if (!Auth()->user()->hasRole('super-admin')) {
                $company_id = Auth()->user()->company_id;
                $positions->where('company_id', $company_id);
            }
            $positions->orderBy('created_at', 'desc');
            $count = $count->get()->count();

            $positions = $positions->simplePaginate(Constant::PAGINATION_LIMIT);
            // dd(DB::getQueryLog());

            $data = [
                'count' => $count,
                'data' => $positions
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
            'position_name' => 'required',
            'position_created_at' => 'date|date_format:Y-m-d',
            'position_ended_at' => 'date|date_format:Y-m-d'
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

            if ((isset($request->id) && !empty($request->id))) {
                $positionExists = Positions::where('id', '!=', $request->id)->where(['company_id' => $companyId, 'status' => true])->where(DB::raw('lower(position_name)'), strtolower($input['position_name']));
                if ($positionExists->exists()) {
                    return ResponseHandler::success([], 'Position with this name already exists.');
                }
                $positionId = $request->id;
                $op = 'update';
            } else {
                $positionExists = Positions::where(['company_id' => $companyId, 'status' => true])->where(DB::raw('lower(position_name)'), strtolower($input['position_name']));
                if ($positionExists->exists()) {
                    return ResponseHandler::success([], 'Position with this name already exists.');
                }
            }

            $positionArr = [
                'position_name' => $input['position_name'],
                'position_created_at' => $now->format('Y-m-d'),
                'position_ended_at' => $now->format('Y-m-d'),
                'status' => true
            ];
            $msg = '';

            if ($op == 'create') {
                $positionArr['company_id'] = $companyId;

                if (Positions::create($positionArr)) {
                    $msg = 'Position has been added succesfully.';
                }
            }

            if ($op == 'update') {

                if (Positions::where(['id' => $positionId, 'company_id' => $companyId, 'status' => true])->update($positionArr)) {
                    $msg = 'Position has been updated succesfully.';
                }
            }

            $res = Positions::where($positionArr)->first();
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
            return ResponseHandler::validationError(['error' => 'Validation Error.', 'position_id' => 'position_id field is required.']);
        }

        $companyId = Auth()->user()->company_id;
        
        if($positionsDetail = Positions::with(['company' => function ($query) {
            $query->select('id', 'business_type', 'company_name', 'company_department', 'company_started_at', 'company_ended_at')
                ->where('status', 1);
        }])->where(['company_id' => $companyId, 'status' => true])->find($id)) {
            return ResponseHandler::success($positionsDetail);
        } else {
            return ResponseHandler::success([], 'Position not found.');
        }
       
    }


    public function destroy($id)
    {
        //
        try {
            $companyId = Auth()->user()->company_id;
            if (Positions::whereId($id)->where('status', true)->exists()) {
                if (Positions::whereId($id)->where('company_id', $companyId)->update(['status' => false])) {
                    return ResponseHandler::success(['Position has been deleted successfully']);
                }
            } else {
                return ResponseHandler::validationError(['Position not found']);
            }
        } catch (\Exception $e) {
            return ResponseHandler::serverError($e);
        }
    }
}
