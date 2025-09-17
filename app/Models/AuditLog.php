<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id','action','details','ip_address','user_agent','http_status'];

    protected $casts = [
        'details' => 'array',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
