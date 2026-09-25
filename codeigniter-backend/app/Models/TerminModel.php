<?php

namespace App\Models;

use CodeIgniter\Model;

class TerminModel extends Model
{
    protected $table = 'termini';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'tutor_id',
        'predmet_id',
        'datum',
        'vrijeme_od',
        'vrijeme_do',
        'cijena',
        'status'
    ];

    protected $useTimestamps = false;
}