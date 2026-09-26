<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Predmet extends Model
{
    protected $table = 'predmeti';

    protected $fillable = [
        'naziv',
        'opis',
    ];

    public $timestamps = false;
}