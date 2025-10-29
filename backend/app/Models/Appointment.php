<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['patient_name', 'doctor', 'datetime', 'status', 'created_at', 'updated_at'];

    protected $casts = [
        'datetime' => 'datetime',
    ];
}
