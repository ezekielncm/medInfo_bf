<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabResult extends Model
{
    protected $fillable = ['patient_id','performed_by','test_name','result','result_date'];

    public function patient() {
        return $this->belongsTo(Patient::class);
    }
}
