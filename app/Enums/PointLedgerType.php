<?php

namespace App\Enums;

enum PointLedgerType: string
{
    case Self = 'self';
    case Override = 'override';
    case Adjustment = 'adjustment';
}
