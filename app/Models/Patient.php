<?php
// app/Models/Patient.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['user_id', 'national_id', 'dob', 'address', 'phone'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function doctors() {
        return $this->belongsToMany(User::class, 'doctor_patient');
    }
}
