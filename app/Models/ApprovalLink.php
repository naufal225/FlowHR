<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalLink extends Model {
    protected $fillable = [
        'model_type','model_id','approver_user_id','level','scope','token','expires_at'
    ];
    protected $casts = ['expires_at' => 'datetime','used_at'=>'datetime'];

    public function approver(){ return $this->belongsTo(User::class,'approver_user_id'); }
    public function subject(){ return $this->morphTo(__FUNCTION__, 'model_type', 'model_id'); }
    public function isValid(): bool {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}
