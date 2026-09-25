<?php

namespace App\Models;

use CodeIgniter\Model;

class TutorPredmetModel extends Model
{
    protected $table = 'tutor_predmet';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'tutor_id',
        'predmet_id'
    ];

    protected $useTimestamps = false;
}