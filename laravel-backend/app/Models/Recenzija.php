<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recenzija extends Model
{
    protected $table = 'recenzije';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'tutor_id',
        'student_id',
        'ocjena',
        'komentar',
    ];

    public function tutor()
    {
        return $this->belongsTo(Korisnik::class, 'tutor_id');
    }

    public function student()
    {
        return $this->belongsTo(Korisnik::class, 'student_id');
    }
}