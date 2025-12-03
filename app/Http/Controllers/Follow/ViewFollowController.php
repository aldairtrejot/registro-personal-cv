<?php

namespace App\Http\Controllers\Follow;

use App\Http\Controllers\Controller;
use App\Models\Follow\CheckPositionModel;

class ViewFollowController extends Controller
{
    public function follow()
    {
        $check = new CheckPositionModel();

        $hasPosition = $check->checkPosition();
        $positionId  = (int) ($check->currentSigPositionId() ?? 0);

        if ($hasPosition) {
            return view('follow.follow', [
                'hasPosition' => $hasPosition,
                'positionId'  => $positionId,
            ]);
        }

        return view('follow.followUpdate', [
            'hasPosition' => false,
            'positionId'  => 0,
        ]);
    }
}
