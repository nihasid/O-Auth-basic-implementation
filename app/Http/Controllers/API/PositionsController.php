<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseHandler;
use Illuminate\Http\Request;
use App\Http\Controllers\API\BaseController as BaseController;
use App\Models\Positions;

class PositionsController extends BaseController
{
    //
    public function index(Request $request) {
        $positionsData = Positions::getAllPositions();
        $data = [
            'count' => count($positionsData),
            'data'  => $positionsData
        ];
        return ResponseHandler::success($data);
    }
}
