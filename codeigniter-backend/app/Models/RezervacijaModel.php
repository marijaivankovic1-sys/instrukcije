<?php

namespace App\Models;

use CodeIgniter\Model;

class RezervacijaModel extends Model
{
    protected $table = 'rezervacije';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'termin_id',
        'student_id',
        'napomena',
        'privitak_putanja',
        'status',
        'datum_rezervacije'
    ];

    protected $useTimestamps = false;
}