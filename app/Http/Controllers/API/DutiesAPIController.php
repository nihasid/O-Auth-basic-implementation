<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseHandler;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Duties;
use Illuminate\Http\Request;

class DutiesAPIController extends BaseController
{
    //
    public function index(Request $request) {
        $dutiesData = Duties::getAllDuties();
        $data = [
            'count' => count($dutiesData),
            'data'  => $dutiesData
        ];
        return ResponseHandler::success($data);
    }
}
