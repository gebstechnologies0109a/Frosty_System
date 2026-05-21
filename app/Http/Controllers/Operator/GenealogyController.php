<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Services\GenealogyEngine;
use App\Services\QualificationEngine;
use App\Support\FrostySettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class GenealogyController extends Controller
{
    public function __invoke(GenealogyEngine $genealogy, QualificationEngine $qualification): View
    {
        $operator = Auth::user();
        $month = FrostySettings::currentMonth();
        $selfQualification = $qualification->rebuildFromLedger($operator, $month);
        $downlines = $genealogy->downlinesByLevel($operator);

        foreach ($downlines as $level => $users) {
            foreach ($users as $user) {
                $q = $qualification->rebuildFromLedger($user, $month);
                $user->setAttribute('personal_points', $q->personal_points);
                $user->setAttribute('qualified', $q->qualified);
            }
        }

        return view('operator.genealogy', [
            'operator' => $operator,
            'selfQualification' => $selfQualification,
            'downlines' => $downlines,
            'threshold' => FrostySettings::qualificationPoints(),
            'month' => $month,
        ]);
    }
}
