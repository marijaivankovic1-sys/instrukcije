<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kosarica extends Model
{
    protected $table = 'kosarica_stavke';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'korisnik_id',
        'zbirka_id',
        'kolicina',
        'datum_dodavanja',
    ];

    public function korisnik()
    {
        return $this->belongsTo(Korisnik::class, 'korisnik_id');
    }

    public function zbirka()
    {
        return $this->belongsTo(Zbirka::class, 'zbirka_id');
    }
}