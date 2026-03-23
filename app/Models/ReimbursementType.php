<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReimbursementType extends Model
{
    protected $table = 'reimbursement_types';

    protected $fillable = ['name'];

    public function reimbursements() {
        return $this->hasMany(Reimbursement::class, 'reimbursement_type_id');
    }
}
