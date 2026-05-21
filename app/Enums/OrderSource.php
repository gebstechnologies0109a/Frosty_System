<?php

namespace App\Enums;

enum OrderSource: string
{
    case Operator = 'operator';
    case Distributor = 'distributor';
    case Pos = 'pos';
}
