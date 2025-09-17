<?php
// app/Models/Consultation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    protected $fillable = ['patient_id','doctor_id','date','reason','diagnosis'];

    public function patient() {
        return $this->belongsTo(Patient::class);
    }

    public function doctor() {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function prescriptions() {
        return $this->hasMany(Prescription::class);
    }
}
