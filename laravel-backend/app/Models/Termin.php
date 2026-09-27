<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Termin extends Model
{
    protected $table = 'termini';

    public $timestamps = false;

    protected $fillable = [
        'tutor_id',
        'predmet_id',
        'datum',
        'vrijeme_od',
        'vrijeme_do',
        'cijena',
        'status',
    ];

    public function predmet()
    {
        return $this->belongsTo(Predmet::class, 'predmet_id');
    }
}