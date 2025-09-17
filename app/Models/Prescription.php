<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    protected $fillable = ['consultation_id','medicine','dosage','duration','notes'];

    public function consultation() {
        return $this->belongsTo(Consultation::class);
    }
}
