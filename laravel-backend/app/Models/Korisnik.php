<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Korisnik extends Model
{
    protected $table = 'korisnici';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'ime',
        'prezime',
        'email',
        'lozinka',
        'uloga',
        'status',
    ];

    protected $hidden = [
        'lozinka',
    ];
}