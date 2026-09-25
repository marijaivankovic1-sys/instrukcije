<?php

namespace App\Models;

use CodeIgniter\Model;

class KorisnikModel extends Model
{
    protected $table = 'korisnici';
    protected $primaryKey = 'id';

    protected $returnType = 'array';

    protected $allowedFields = [
        'ime',
        'prezime',
        'email',
        'lozinka',
        'uloga',
        'status'
    ];

    protected $useTimestamps = false;
}