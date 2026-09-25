<?php

namespace App\Models;

use CodeIgniter\Model;

class KosaricaModel extends Model
{
    protected $table = 'kosarica_stavke';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'korisnik_id',
        'zbirka_id',
        'kolicina',
        'datum_dodavanja'
    ];

    protected $useTimestamps = false;
}