<?php

namespace App\Models;

use CodeIgniter\Model;

class RecenzijaModel extends Model
{
    protected $table = 'recenzije';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'tutor_id',
        'student_id',
        'ocjena',
        'komentar'
    ];

    protected $useTimestamps = false;
}