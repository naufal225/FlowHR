<?php

namespace App\Enums;

enum TypeRequest : String
{
    case Leaves = "cuti";
    case Reimbursements = "reimbursement";
    case Overtimes = "overtime";
    case Travels = "travel";
}
