<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TutorPredmet extends Model
{
    protected $table = 'tutor_predmet';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tutor_id',
        'predmet_id',
    ];

    public function tutor()
    {
        return $this->belongsTo(Korisnik::class, 'tutor_id');
    }

    public function predmet()
    {
        return $this->belongsTo(Predmet::class, 'predmet_id');
    }
}