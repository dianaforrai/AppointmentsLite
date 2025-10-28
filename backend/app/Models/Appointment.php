<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['patient_name', 'doctor', 'datetime', 'status'];

    protected $casts = [
        'datetime' => 'datetime',
    ];
}
