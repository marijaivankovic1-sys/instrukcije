<?php

namespace App\Models;

use CodeIgniter\Model;

class ZbirkaModel extends Model
{
    protected $table = 'zbirke';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'predmet_id',
        'naziv',
        'opis',
        'cijena',
        'slika_putanja',
        'pdf_putanja'
    ];

    protected $useTimestamps = false;
}