<?php

namespace App\Models;

use CodeIgniter\Model;

class PredmetModel extends Model
{
    protected $table = 'predmeti';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'naziv',
        'opis'
    ];

    protected $useTimestamps = false;
}