<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zbirka extends Model
{
    protected $table = 'zbirke';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'predmet_id',
        'naziv',
        'opis',
        'cijena',
        'slika_putanja',
        'pdf_putanja',
    ];

    public function predmet()
    {
        return $this->belongsTo(Predmet::class, 'predmet_id');
    }
}